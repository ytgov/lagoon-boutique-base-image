<?php
namespace Drupal\biz_business_rules\Controller;

    class BusinessRulesFunctions{
        public $module = "biz_business_rules";
        public $key = "mail";
        //Get all mails from the specific role
        static function getAllMailFromRole($role){
            $delivery_emails = [];
            $ids = \Drupal::entityQuery('user')
            ->condition('status', 1)
            ->condition('roles', $role)
            ->accessCheck(FALSE)
            ->execute();
            $users = \Drupal\user\Entity\User::loadMultiple($ids);
            foreach($users as $user){
                $user_email = $user->get('mail')->value;
                $delivery_emails[] = ['mail' => $user_email, 'langcode' => 'en'];
            }
            \Drupal::logger("biz_business_rules")->notice("getAllMailFromRole: " . json_encode($delivery_emails));
            return $delivery_emails;
        }
        //Validate if is the first activity from a user
        static function isFirstActivity($webform_id, $user_id, $is_active = NULL){
            $database = \Drupal::database();
            $query = $database->select('webform_submission', 'ws');
            // Add extra detail to this query object
            $query->condition('ws.webform_id', $webform_id, '=');
            $query->condition('ws.uid', $user_id, '=');
            $query->fields('ws', ['sid']);
            if(!empty($is_active)){
                $query->join('webform_submission_data', 'wsd', 'ws.sid = wsd.sid');
                $query->condition('wsd.name', 'status', '=');
                $query->condition('wsd.value','Active', '=');
            }
            $num_rows = $query->countQuery()->execute()->fetchField(0);
            \Drupal::logger("biz_business_rules")->notice("isFirstActivity: " . $num_rows);
            if(!empty($is_active)){
                return $num_rows > 0 ? TRUE : FALSE;
            }else{
                return $num_rows > 1 ? FALSE : TRUE;
            }
        }

        //Get all emails from all the in-house lobbyist didn't  certify yet
        static function getInHouseEmailsNotCertifyYet() {
            $dates = self::getDatesCalendarEnd();
            $database = \Drupal::database();
            $query = $database->select('webform_submission', 'ws');
            $query->join('webform_submission_data', 'wsd_status', "ws.sid = wsd_status.sid AND wsd_status.name = 'frontend_status' AND wsd_status.value = 'active'");
            $query->join('users_field_data', 'ufd', 'ws.uid = ufd.uid AND ufd.status = 1');
            $query->leftJoin('webform_submission_log', 'wsl', 'ws.sid = wsl.sid AND ws.uid = wsl.uid');
            $query->join('user__field_legal_organization', 'uflo', 'ws.uid = uflo.entity_id AND uflo.bundle = :bundle', [':bundle' => 'user']);
            $query->fields('ufd', ['mail']);
            $query->addField('ufd', 'preferred_langcode', 'langcode');
            $query->addField('uflo', 'field_legal_organization_value', 'organization');
            $query->addField('ws', 'sid', 'sid');
            $query->condition('ws.webform_id', 'add_a_lobbying_activity');

            // Validate timestamp is not null
            $query->where('(wsl.timestamp IS NULL OR wsl.timestamp NOT BETWEEN :from_ts AND :to_ts)', [
                ':from_ts' => strtotime($dates['from']),
                ':to_ts' => strtotime($dates['to']),
            ]);
            $query->groupBy('ufd.mail');
            $query->groupBy('ufd.preferred_langcode');
            $query->groupBy('uflo.field_legal_organization_value');
            $query->groupBy('ws.sid');
            $query->orderBy('ufd.mail');
            $query->orderBy('ws.sid');
            $results = $query->execute()->fetchAll();

            // Group by email
            $grouped = [];
            foreach ($results as $row) {
                if (empty($row->mail) || empty($row->sid)) {
                    continue;
                }
                $email = $row->mail;
                if (!isset($grouped[$email])) {
                    $grouped[$email] = [
                        'mail' => $row->mail,
                        'langcode' => $row->langcode ?? 'en',
                        'organization' => $row->organization ?? '',
                        'sids' => [],
                    ];
                }
                $grouped[$email]['sids'][] = $row->sid;
            }

            return array_values($grouped);
        }

        //Get all emails from all the consultant lobbyist didn't  certify yet
        static function getConsultantNotCertifyYet(){
            $database = \Drupal::database();
            $query = $database->select('webform_submission', 'ws');
            $query->join('webform_submission_data', 'wsd', "ws.sid = wsd.sid     and wsd.name = 'start_date'");
            $query->join('webform_submission_data', 'wsd_status', "ws.sid = wsd_status.sid and wsd_status.name = 'frontend_status' and  wsd_status.value = 'active'");
            $query->leftJoin('webform_submission_log', 'wsl', "ws.sid = wsl.sid AND ws.uid = wsl.uid AND operation = 'submission_updated' ");
            $query->fields('ws', ['sid']);
            $query->condition('ws.webform_id', 'add_a_lobbying_activity_consulta', '=');
            $where = "DATE_ADD(DATE_ADD(wsd.value, INTERVAL ( (SELECT    FLOOR(TIMESTAMPDIFF(MONTH,    wsd.value, CURRENT_DATE) / 6 ) )* 6)    MONTH), INTERVAL 1 DAY) = CURRENT_DATE
                OR DATE_ADD(DATE_ADD(wsd.value, INTERVAL ( (SELECT    FLOOR(TIMESTAMPDIFF(MONTH,    wsd.value, CURRENT_DATE) / 6 ) )* 6)    MONTH), INTERVAL 15 DAY) = CURRENT_DATE
                OR DATE_ADD(DATE_ADD(wsd.value, INTERVAL ( (SELECT    FLOOR(TIMESTAMPDIFF(MONTH,    wsd.value, CURRENT_DATE) / 6 ) )* 6)    MONTH), INTERVAL 30 DAY) = CURRENT_DATE OR wsd.value = CURRENT_DATE";
            $query->where($where);

            $query2 = $database->select('webform_submission', 'ws');
            $query2->join('webform_submission_data', 'wsd', "ws.sid = wsd.sid     and wsd.name = 'end_date'");
            $query2->leftJoin('webform_submission_log', 'wsl', "ws.sid = wsl.sid AND ws.uid = wsl.uid AND operation = 'submission_updated' ");
            $query2->join('webform_submission_data', 'wsd_status', "ws.sid = wsd_status.sid and wsd_status.name = 'frontend_status' and  wsd_status.value = 'active'");
            $query2->fields('ws', ['sid']);
            $query2->condition('ws.webform_id', 'add_a_lobbying_activity_consulta', '=');

            $query2->where($where);
            $consultant_query = $query->union($query2, 'DISTINCT');
            $results = $consultant_query->execute()->fetchAll();

            \Drupal::logger("biz_business_rules")->notice("getConsultantNotCertifyYet:" . json_encode($results));
            return $results;
        }
        //Update status activities 
        static function updatedActiveActivities($webform_id, $field, $value, $old_value, $sid = NULL){
            $dates = self::getDatesCalendarEnd();
            $from = $dates['from'];
            $to = $dates['to'];
            $database = \Drupal::database();
            $all_sid = $sid;
            if(empty($sid)){
                $subquery = $database->select('webform_submission', 'ws');
                $subquery->join('webform_submission_data', 'wsd', 'ws.sid = wsd.sid');
                $subquery->join('webform_submission_data', 'wsd_status', "ws.sid = wsd_status.sid and wsd_status.name = 'frontend_status' and  wsd_status.value = 'active'");
                $subquery->fields('ws', ['sid']);
                $subquery->condition('ws.webform_id',    $webform_id, '=');
                $subquery->condition('wsd.name', $field, '=');
                if(!empty($old_value)){
                    $subquery->condition('wsd.value', $old_value , '=');
                }

                $where = "DATE_FORMAT(FROM_UNIXTIME(changed), '%Y-%m-%d') >= " .    $from . " AND DATE_FORMAT(FROM_UNIXTIME(changed), '%Y-%m-%d') <=" .$to ;
                $subquery->where($where);
                $subquery->groupBy('ws.sid');
                $sid = $subquery->execute()->fetchAll();
                $all_sid = [];
                foreach($sid as $key_query => $value_query){
                  $all_sid[] = intval($value_query->sid);
                }
            }

            if(!empty($all_sid)){
                $num_updated = $database->update('webform_submission_data')
                ->fields([ 'value' => $value])
                ->condition('sid', $all_sid, 'IN')
                ->condition('name', $field, '=')
                ->execute();
                \Drupal::logger("biz_business_rules")->notice('updatedActiveActivities:' . json_encode($num_updated));
            }

        }
        //Get the dates from in-house calendar end 
        static function getDatesCalendarEnd(){
            $today = date("Y-m-d"); // Today
            $from = "";
            $to = "";
            $current_month = date('m');
            if ($current_month == '12') {
                $nextYear = date('Y', strtotime('+1 year'));
                $from = date(date('Y') . '-12-31');
                $to = date($nextYear . '-01-31');
            } else if ($current_month >= 1 && $current_month <= 11) {
                $prevYear = date('Y', strtotime('-1 year'));
                $from = date($prevYear . '-12-31');
                $to = date(date('Y'). '-01-31');
            }

            return ['to' => $to, 'from' => $from];
        }
        //Get dates to validate if the consultant activity is close to being ending
        static function getDatesContract($contract_date, $end_contract = FALSE){
            $after_6_month = $contract_date;
            $datetime1 = date_create();
            $datetime2 = date_create($contract_date);
            // calculates the difference between contract date and today
            $interval = date_diff($datetime1, $datetime2);
            $diff_months = intval($interval->format('%m'));
            $diff_years= intval($interval->format('%y'));
            $months = (floor($diff_months / 6) == 0 ? 1: floor($diff_months / 6)) * 6;
            $years = floor($diff_years) == 0 ? 0: floor($diff_years * 12);
            $total_months = $months + $years;
            if(!$end_contract){
              $after_6_month = date("Y-m-d", strtotime( $contract_date . ' +'. $total_months .' month'));
            }
            $from = date("Y-m-d", strtotime($after_6_month . ' +1 day'));
            $middle = date("Y-m-d", strtotime($after_6_month . ' +15 day'));
            $to = date("Y-m-d", strtotime( $after_6_month . ' +30 day'));
            return    ['from' => $from, 'middle' => $middle, 'to' => $to];
        }
        //Get the last updated date by owner
        static function getLastUpdatedByOwner($webform_id, $submission_id, $user_id){
            $query = \Drupal::database()->select('webform_submission_log', 'wsl')
                ->condition('wsl.sid', $submission_id,'=')
                ->condition('wsl.uid', $user_id,'=')
                ->condition('wsl.webform_id', $webform_id,'=' )
                ->orderBy('wsl.timestamp', 'DESC')
                ->range(0, 1);
            $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(wsl.timestamp), '%Y-%m-%d')", 'last_updated');
            $result = $query->execute()->fetchAll();
            if(isset($result[0]) && isset($result[0]->last_updated)){
                return $result[0]->last_updated;
            }
            return ''; 
        }
    }

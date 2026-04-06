<?php
namespace Drupal\biz_business_rules\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
* Configure example settings for this site.
*/
class BusinessRulesSettingsForm extends ConfigFormBase {

    /**
    * Config settings.
    *
    * @var string
    */
    const SETTINGS = 'biz_business_rules.settings';

    /**
    * {@inheritdoc}
    */
    public function getFormId() {
        return 'biz_business_rules_admin_settings';
    }

    /**
    * {@inheritdoc}
    */
    protected function getEditableConfigNames() {
        return [
            static::SETTINGS,
        ];
    }

    /**
    * {@inheritdoc}
    */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config(static::SETTINGS);
        $form['front_base_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Front: Base URL"),
            '#default_value' => $config->get('front_base_url'),
        ];
        
        $form['in_house'] = array(
            '#type' => 'fieldset',
            '#title' => t('In-house'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
        );
        
        $form['in_house']['in_house_subject_add_new_lobbyist'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when added a new lobbyist'),
            '#default_value' => $config->get('in_house_subject_add_new_lobbyist'),
        ];
    
        $form['in_house']['in_house_add_new_lobbyist'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to in-house lobbyist when added a new lobbyist'),
            '#default_value' => $config->get('in_house_add_new_lobbyist')["value"],
        ];
        $form['in_house']['in_house_subject_add_new_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when added a new activity'),
            '#default_value' => $config->get('in_house_subject_add_new_activity'),
        ];
        
        $form['in_house']['in_house_add_new_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to in-house lobbyist when added a new activity'),
            '#default_value' => $config->get('in_house_add_new_activity')["value"],
        ];

        $form['in_house']['in_house_subject_end_31_dec'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when the calendar year ends'),
            '#default_value' => $config->get('in_house_subject_end_31_dec'),
        ];
        
        $form['in_house']['in_house_end_31_dec'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to in-house lobbyist when the calendar year ends'),
            '#default_value' => $config->get('in_house_end_31_dec')["value"],
        ];

        $form['in_house']['in_house_subject_end_15_jan'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when is the end of the calendar year +15 days'),
            '#default_value' => $config->get('in_house_subject_end_15_jan'),
        ];
        
        $form['in_house']['in_house_end_15_jan'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to in-house lobbyist when is the end of the calendar year +15 days'),
            '#default_value' => $config->get('in_house_end_15_jan')["value"],
        ];

        $form['in_house']['in_house_subject_end_31_jan'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when is the end of the calendar year +31 days'),
            '#default_value' => $config->get('in_house_subject_end_31_jan'),
        ];

        $form['in_house']['in_house_end_31_jan'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to in-house lobbyist when is the end of the calendar year +31 days'),
            '#default_value' => $config->get('in_house_end_31_jan')["value"],
        ];

        $form['in_house']['in_house_subject_update_before_end_calendar'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when updated previous calendar year'),
            '#default_value' => $config->get('in_house_subject_update_before_end_calendar'),
        ];
        
        $form['in_house']['in_house_update_before_end_calendar'] = [
            '#type' => 'text_format',
            '#title' => $this->t("Mail to in-house lobbyist when updated previous calendar year"),
            '#default_value' => $config->get('in_house_update_before_end_calendar')["value"],
        ];

        $form['in_house']['in_house_subject_first_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject in-house lobbyist when created their first activity'),
            '#default_value' => $config->get('in_house_subject_first_activity'),
        ];
        
        $form['in_house']['in_house_first_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to in-house lobbyist when created their first activity'),
            '#default_value' => $config->get('in_house_first_activity')["value"],
        ];

        $form['consultant'] = array(
            '#type' => 'fieldset',
            '#title' => t('Consultant'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
        );

        $form['consultant']['consultant_subject_add_new_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant lobbyist when added a new activity'),
            '#default_value' => $config->get('consultant_subject_add_new_activity'),
        ];
        
        $form['consultant']['consultant_add_new_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant lobbyist when added a new activity'),
            '#default_value' => $config->get('consultant_add_new_activity')["value"],
        ];

        $form['consultant']['consultant_subject_first_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant lobbyist when created their first activity'),
            '#default_value' => $config->get('consultant_subject_first_activity'),
        ];
        
        $form['consultant']['consultant_first_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant lobbyist when created their first activity'),
            '#default_value' => $config->get('consultant_first_activity')["value"],
        ];

        $form['consultant']['consultant_subject_certify'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when certify their activity'),
            '#default_value' => $config->get('consultant_subject_certify'),
        ];
        
        $form['consultant']['consultant_certify'] = [
            '#type' => 'text_format',
            '#title' => $this->t("Mail to consultant when certify their activity"),
            '#default_value' => $config->get('consultant_certify')["value"],
        ];

        $form['consultant']['consultant_subject_prior_6_months'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when the start date is prior to 6 Months from today'),
            '#default_value' => $config->get('consultant_subject_prior_6_months'),
        ];
        
        $form['consultant']['consultant_prior_6_months'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant when the start date is prior to 6 Months from today'),
            '#default_value' => $config->get('consultant_prior_6_months')["value"],
        ];

                
         $form['consultant']['consultant_subject_prior_6_months_plus_15'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when start date is prior 6 Months +15 days'),
            '#default_value' => $config->get('consultant_subject_prior_6_months_plus_15'),
        ];

        $form['consultant']['consultant_prior_6_months_plus_15'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant when start date is prior 6 Months +15 days'),
            '#default_value' => $config->get('consultant_prior_6_months_plus_15')["value"],
        ];

        $form['consultant']['consultant_subject_prior_6_months_plus_30'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when start date is prior 6 Months +30 days'),
            '#default_value' => $config->get('consultant_subject_prior_6_months_plus_30'),
        ];
        
        $form['consultant']['consultant_prior_6_months_plus_30'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant when start date is prior 6 Months +30 days'),
            '#default_value' => $config->get('consultant_prior_6_months_plus_30')["value"],
        ];

        $form['consultant']['consultant_subject_end_contract'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when the contract date ends'),
            '#default_value' => $config->get('consultant_subject_end_contract'),
        ];
        
        $form['consultant']['consultant_end_contract'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant when the contract date ends'),
            '#default_value' => $config->get('consultant_end_contract')["value"],
        ];

        $form['consultant']['consultant_subject_end_contract_plus_15'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when end of contract date +15 days'),
            '#default_value' => $config->get('consultant_subject_end_contract_plus_15'),
        ];
        
        $form['consultant']['consultant_end_contract_plus_15'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant when end of contract date +15 days'),
            '#default_value' => $config->get('consultant_end_contract_plus_15')["value"],
        ];

        $form['consultant']['consultant_subject_end_contract_plus_30'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject consultant when end of contract date +30 days'),
            '#default_value' => $config->get('consultant_subject_end_contract_plus_30'),
        ];
        
        $form['consultant']['consultant_end_contract_plus_30'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to consultant when end of contract date +30 days'),
            '#default_value' => $config->get('consultant_end_contract_plus_30')["value"],
        ];

        $form['admin'] = array(
            '#type' => 'fieldset',
            '#title' => t('Administrator'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
        );

        $form['admin']['admin_subject_end_consultant_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject admin when consultant non-compliance the sixth month'),
            '#default_value' => $config->get('admin_subject_end_consultant_activity'),
        ];
        
        $form['admin']['admin_end_consultant_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to admin when consultant non-compliance the sixth month'),
            '#default_value' => $config->get('admin_end_consultant_activity')["value"],
        ];
        $form['admin']['admin_subject_contract_end_date'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject admin when the contract ends in the consultant activity'),
            '#default_value' => $config->get('admin_subject_contract_end_date'),
        ];
        $form['admin']['admin_contract_end_date'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to admin when the contract ends in the consultant activity'),
            '#default_value' => $config->get('admin_contract_end_date')["value"],
        ];
        
        $form['admin']['admin_subject_end_31_jan'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject admin when the calendar year ends +31 days'),
            '#default_value' => $config->get('admin_subject_end_31_jan'),
        ];
        
        $form['admin']['admin_end_31_jan'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to admin when the calendar year ends +31 days'),
            '#default_value' => $config->get('admin_end_31_jan')["value"],
        ];

        $form['admin']['admin_subject_in_house_first_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject admin for first in-house activity'),
            '#default_value' => $config->get('admin_subject_in_house_first_activity'),
        ];
        
        $form['admin']['admin_in_house_first_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to admin for first in-house activity'),
            '#default_value' => $config->get('admin_in_house_first_activity')["value"],
        ];
        $form['admin']['admin_subject_consultant_first_activity'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject admin for first consultant activity'),
            '#default_value' => $config->get('admin_subject_consultant_first_activity'),
        ];

        $form['admin']['admin_consultant_first_activity'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to admin for first consultant activity'),
            '#default_value' => $config->get('admin_consultant_first_activity')["value"],
        ];
        
        $form['admin']['lobbyist_subject_new_comment'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject admin when a comment is made in an activity by the lobbyist'),
            '#default_value' => $config->get('lobbyist_subject_new_comment'),
        ];
        
        $form['admin']['lobbyist_new_comment'] = [
            '#type' => 'text_format',
            '#title' => $this->t("Mail to admin when a comment is made in an activity by the lobbyist"),
            '#default_value' => $config->get('lobbyist_new_comment')["value"],
        ];
        $form['admin']['lobbyist_updated_act'] = [
            '#type' => 'text_format',
            '#title' => $this->t("Mail to commissioner when lobbyist updated an activity"),
            '#default_value' => $config->get('lobbyist_updated_act')["value"],
        ];

        $form['lobbyist'] = array(
            '#type' => 'fieldset',
            '#title' => t('For both lobbyist type'),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
        );
        
        $form['lobbyist']['admin_subject_new_comment'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject lobbyist when a comment is made in an activity by the admin'),
            '#default_value' => $config->get('admin_subject_new_comment'),
        ];

        $form['lobbyist']['admin_new_comment'] = [
            '#type' => 'text_format',
            '#title' => $this->t("Mail to lobbyist when a comment is made in an activity by the admin"),
            '#default_value' => $config->get('admin_new_comment')["value"],
        ];
        
        $form['lobbyist']['commissioner_subject_approve_first_act'] = [
            '#type' => 'textfield',
            '#maxlength' => 500,
            '#title' => $this->t('Subject lobbyist when commissioner approve their first activity'),
            '#default_value' => $config->get('commissioner_subject_approve_first_act'),
        ];
        
        $form['lobbyist']['commissioner_approve_first_act'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to lobbyist when commissioner approve their first activity'),
            '#default_value' => $config->get('commissioner_approve_first_act')["value"],
        ];
        $form['lobbyist']['commissioner_updated_act'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail to lobbyist when commissioner updated an activity'),
            '#default_value' => $config->get('commissioner_updated_act')["value"],
        ];
        $form['lobbyist']['you_updated_act'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Mail when user updated an activity'),
            '#default_value' => $config->get('you_updated_act')["value"],
        ];
        
        $form['lobbyist']['wave_for_not_validate'] = [
            '#type' => 'checkbox',
            '#title' => $this->t("Not end the activities"),
            '#default_value' => $config->get('wave_for_not_validate'),
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
    * {@inheritdoc}
    */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Retrieve the configuration.
        $this->configFactory->getEditable(static::SETTINGS)
            // Set the submitted configuration setting.
            ->set('front_base_url', $form_state->getValue('front_base_url'))
            ->set('in_house_subject_add_new_lobbyist', $form_state->getValue('in_house_subject_add_new_lobbyist'))
            ->set('in_house_add_new_lobbyist', $form_state->getValue('in_house_add_new_lobbyist'))
            ->set('in_house_subject_add_new_activity', $form_state->getValue('in_house_subject_add_new_activity'))
            ->set('in_house_add_new_activity', $form_state->getValue('in_house_add_new_activity'))
            ->set('in_house_subject_end_31_dec', $form_state->getValue('in_house_subject_end_31_dec'))
            ->set('in_house_end_31_dec', $form_state->getValue('in_house_end_31_dec'))
            ->set('in_house_subject_end_15_jan', $form_state->getValue('in_house_subject_end_15_jan'))
            ->set('in_house_end_15_jan', $form_state->getValue('in_house_end_15_jan'))
            ->set('in_house_subject_end_31_jan', $form_state->getValue('in_house_subject_end_31_jan'))
            ->set('in_house_end_31_jan', $form_state->getValue('in_house_end_31_jan'))
            ->set('in_house_subject_update_before_end_calendar', $form_state->getValue('in_house_subject_update_before_end_calendar'))
            ->set('in_house_update_before_end_calendar', $form_state->getValue('in_house_update_before_end_calendar'))
            ->set('in_house_subject_first_activity', $form_state->getValue('in_house_subject_first_activity'))
            ->set('in_house_first_activity', $form_state->getValue('in_house_first_activity'))

            ->set('consultant_subject_prior_6_months', $form_state->getValue('consultant_subject_prior_6_months'))
            ->set('consultant_prior_6_months', $form_state->getValue('consultant_prior_6_months'))
            ->set('consultant_subject_prior_6_months_plus_15', $form_state->getValue('consultant_subject_prior_6_months_plus_15'))
            ->set('consultant_prior_6_months_plus_15', $form_state->getValue('consultant_prior_6_months_plus_15'))
            ->set('consultant_subject_prior_6_months_plus_30', $form_state->getValue('consultant_subject_prior_6_months_plus_30'))
            ->set('consultant_prior_6_months_plus_30', $form_state->getValue('consultant_prior_6_months_plus_30'))
            ->set('consultant_subject_end_contract', $form_state->getValue('consultant_subject_end_contract'))
            ->set('consultant_end_contract', $form_state->getValue('consultant_end_contract'))
            ->set('consultant_subject_end_contract_plus_15', $form_state->getValue('consultant_subject_end_contract_plus_15'))
            ->set('consultant_end_contract_plus_15', $form_state->getValue('consultant_end_contract_plus_15'))
            ->set('consultant_subject_end_contract_plus_30', $form_state->getValue('consultant_subject_end_contract_plus_30'))
            ->set('consultant_end_contract_plus_30', $form_state->getValue('consultant_end_contract_plus_30'))
            ->set('consultant_subject_add_new_activity', $form_state->getValue('consultant_subject_add_new_activity'))
            ->set('consultant_add_new_activity', $form_state->getValue('consultant_add_new_activity'))
            ->set('consultant_subject_first_activity', $form_state->getValue('consultant_subject_first_activity'))
            ->set('consultant_first_activity', $form_state->getValue('consultant_first_activity'))
            ->set('consultant_subject_certify', $form_state->getValue('consultant_subject_certify'))
            ->set('consultant_certify', $form_state->getValue('consultant_certify'))

            ->set('admin_subject_end_consultant_activity', $form_state->getValue('admin_subject_end_consultant_activity'))
            ->set('admin_end_consultant_activity', $form_state->getValue('admin_end_consultant_activity'))
            ->set('admin_subject_contract_end_date', $form_state->getValue('admin_subject_contract_end_date'))
            ->set('admin_contract_end_date', $form_state->getValue('admin_contract_end_date'))
            ->set('admin_subject_end_31_jan', $form_state->getValue('admin_subject_end_31_jan'))
            ->set('admin_end_31_jan', $form_state->getValue('admin_end_31_jan'))
            ->set('admin_subject_in_house_first_activity', $form_state->getValue('admin_subject_in_house_first_activity'))
            ->set('admin_in_house_first_activity', $form_state->getValue('admin_in_house_first_activity'))
            ->set('admin_subject_consultant_first_activity', $form_state->getValue('admin_subject_consultant_first_activity'))
            ->set('admin_consultant_first_activity', $form_state->getValue('admin_consultant_first_activity'))
            ->set('admin_subject_new_comment', $form_state->getValue('admin_subject_new_comment'))
            ->set('admin_new_comment', $form_state->getValue('admin_new_comment'))

            ->set('lobbyist_subject_new_comment', $form_state->getValue('lobbyist_subject_new_comment'))
            ->set('lobbyist_new_comment', $form_state->getValue('lobbyist_new_comment'))
            ->set('lobbyist_updated_act', $form_state->getValue('lobbyist_updated_act'))
            ->set('you_updated_act', $form_state->getValue('you_updated_act'))
            
            ->set('commissioner_subject_approve_first_act', $form_state->getValue('commissioner_subject_approve_first_act'))
            ->set('commissioner_approve_first_act', $form_state->getValue('commissioner_approve_first_act'))
            ->set('commissioner_updated_act', $form_state->getValue('commissioner_updated_act'))
            ->set('wave_for_not_validate', $form_state->getValue('wave_for_not_validate'))

            ->save();
        parent::submitForm($form, $form_state);
    }
}

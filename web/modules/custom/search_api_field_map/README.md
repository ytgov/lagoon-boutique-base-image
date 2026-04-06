INTRODUCTION
------------

The Search API Field Map module facilitates indexing data from multiple Drupal sites into a single search index.

 * For a full description of the module, visit the project page: 

 * Please use GitHub submit bug reports and feature suggestions, or to track changes:
  https://github.com/palantirnet/search_api_field_map
  
The primary purpose of this module is to allow consistent mapping of data across multiple sites when using [Federated Search](https://www.drupal.org/project/search_api_federated_solr). However, it may be useful in other cases in which token replacement is required for specific search fields.

  
REQUIREMENTS
------------

This module requires the following modules:

 * Search API (https://www.drupal.org/project/search_api)
 * Token (https://www.drupal.org/project/token)


INSTALLATION
------------
 
  * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.
   
   
CONFIGURATION
------------

On each site included in the mapped field search, you will need to:

    1. Configure a Search API server to connect to the index
    2. [Follow these detailed instructions](docs/usage.md) to configure your fields.


TROUBLESHOOTING & FAQ
---------------------

TBD


MAINTAINERS
-----------

Current maintainers:
* Avi Schwab (froboy) - https://www.drupal.org/u/froboy
* Ken Rickard (agentrickard) - https://www.drupal.org/u/agentrickard
* Malak Desai (MalakDesai) - https://www.drupal.org/u/malakdesai
* Matthew Carmichael (mcarmichael21) - https://www.drupal.org/u/mcarmichael21

This project has been sponsored by:
* Palantir.net (https://palantir.net)

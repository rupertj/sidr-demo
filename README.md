# sidr-demo
Example/Demo site for how to use SIDR: A Silex front-end for Drupal.

To use this:

* Set up two websites, the front-end site, pointing to /silex/web in this repo, and the back-end site, pointing to /drupal.
* Install Drupal like normal. Choose the SIDR Demo profile.
* Set up services. (This should be in a feature...)
* Ensure that the machine the front end is installed on can resolve the address of the backend. (They can be the same machine).
* Copy /silex/default.settings.php to /silex/settings.php and edit it, including the address of the backend and the services endpoint.
* Run composer install in /silex.

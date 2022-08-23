# Necessary to run
 - MySQL 8+
 - PHP 8.1+
## Run before run the test
 - `php bin/console --env=test doctrine:database:create`
 - `php bin/console --env=test doctrine:schema:create`
## Sending e-mails
To send e-mails the MAILER_DSN env variable must be defined first.
# mijnknltb2gsuite
Syncs matches on mijnknltb.toernooi.nl to Google Calendar en Sheets

Requires php, php-curl and php-dom. Additional requirement for integration with GSuite: https://github.com/googleapis/google-api-php-client

Install php on Ubuntu
- sudo apt update
- sudo apt install -y php php-curl php-dom composer

Install Google API Client Library for PHP
- composer require google/apiclient:^2.0
- save gsuite service account file and allow access to the gsuite calendar to service account
- edit config.ini

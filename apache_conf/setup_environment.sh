#!/bin/bash

# SYSTEM UPDATE

# Updating the system package lists
apt-get update -y

# Installing unzip utility
apt install unzip -y


# INSTALL BASE TOOLS

# Installing essential tools: vim and net-tools
apt-get install -y vim
apt-get install -y net-tools


# INSTALL AND CONFIGURE COMPOSER

# Downloading Composer installer script
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

# Verifying the installer script with the hash
HASH=$(curl -sS https://composer.github.io/installer.sig)
php -r "if (hash_file('SHA384', '/tmp/composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

# Installing Composer globally
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Using Composer to install required PHP packages
(cd /var/www/html && composer require phpmailer/phpmailer)
(cd /var/www/html && composer install)


# E-BOOK FILE SETUP

# Creating and populating e-books directory
mkdir -p /home/bookselling/e-books
cp /home/bookselling/e-books-mounted/* /home/bookselling/e-books


# MYSQL TOOLS INSTALLATION

# Installing MySQL development libraries
apt-get install -y libmysqli-dev

# Installing and enabling MySQLi PHP extension
docker-php-ext-install mysqli
docker-php-ext-enable mysqli


# APACHE CONFIGURATION

# Enabling SSL and the site configuration for Apache
a2enmod ssl
a2ensite bookselling.conf

# Reloading Apache to apply changes
service apache2 reload

# Running Apache in the foreground
apache2-foreground

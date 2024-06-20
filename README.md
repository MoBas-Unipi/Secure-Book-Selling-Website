# Secure Book Selling Website

University project for **Systems and Network Hacking** course (MSc Computer Engineering at University of Pisa, A.Y. 2023-24)   


## Programming Languages and Tools
<div align="center">
  <a href="https://skillicons.dev">
    <img src="https://skillicons.dev/icons?i=php,html,css,js," />
  </a>
</div>

<div align="center">
  <a href="https://skillicons.dev">
    <img src="https://skillicons.dev/icons?i=docker,mysql," />
  </a>
</div>


## Overview

**Bookselling** is a web project designed to simulate a secure e-commerce website.
The site offers the following features:

A user can visit the website and explore the available books in the catalog. To proceed with a purchase, the user must first register on the site by providing their email and creating a password. After registration, the user needs to log in using their credentials.

Once logged in, the user can add desired books to the cart and proceed to checkout. The checkout process involves several steps: inserting credit card details, providing shipping information, and confirming the purchase.

After making a purchase, the user can view their orders in the "Purchases" section, where they also have the option to download the books they have bought. 

Additionally, the user can visit their personal information page to change their password if needed.
If the user forgets their password, the site offers a "password recovery" feature. This feature uses an OTP sent to the registered email to facilitate password reset.


## Security Requirements
In the project, all security aspects regarding the following potential attacks or vulnerabilities have been guaranteed.

- **Broken Authentication**
  - Weak Password Check
  - Multiple Login Attempts
  - Session Hijacking and Session Fixation
  - Credential Storage and Management
  - Password Change and Account Recovery
  
- **SQL Injection**
- **Cross Site Scripting (XSS)**
- **Cross Site Request Forgery (XSRF)**
- **Multi-Step Procedure Attacks**
<br>


# System Configuration and Execution
<br>

## SSL/TLS Certificates Generation

* Set executable permissions to the script in order to produce the certificates
```
chmod +x config_certificates.sh
```


* Generate the certificates with the following commands:
```
cd apache_conf/ssl_conf/certificates

./config_certificates.sh
```

* Import the SNH_CA.pem in the browser. The CA certificate is located in the following directory: 
```
apache_conf/ssl_conf/certificates
```
<br>


## BookSelling Website Preparation and Execution

* Add this rule in the ```/etc/hosts``` file:
```
127.0.0.1 www.bookselling.snh
```

* Set executable permissions to "***manage_docker***" script, in order to execute the system 
```
chmod +x manage_docker.sh
```

* Load the System and start all the services:
```
./manage_docker.sh start
```

* Access the WebSite with the following URL:
```
www.bookselling.snh
```
<br>

## Other Useful Commands

- ```./manage_docker.sh stop``` **Stop all running Docker containers and services**
- ```./manage_docker.sh status``` **Show status of all Docker containers and services**
- ```./manage_docker.sh remove_containers``` **Remove all stopped Docker containers**
- ```./manage_docker.sh image_status``` **Show status of Docker images**
- ```./manage_docker.sh remove_images``` **Remove all Docker images**
- ```./manage_docker.sh remove_network``` **Remove the bookselling specific Docker network**
<br>


## Bookselling MySQL Database
* Access the PhpMyAdmin Portal type the following URL in the browser:
```
localhost:8800
```

* Access the MySQL DataBase with the following credentials:
```
host: localhost
user: root or the one in the .env file
password: in the .env file
database: in the .env file
```
<br>


# Authors
- [Francesco Martoccia](https://github.com/FrankMartoccia)
- [Luca Tartaglia](https://github.com/LucT3)
- [Salvatore Lombardi](https://github.com/salbh)


# Unbound DNS Server Web Interface

We searched a lot of web interfaces for Unbound DNS Server but couldn't find any worthwhile one.

We tried to enable the community to manage Unbound DNS in a more useful way, especially in test environments.

If you have any requests, please let us know.


## Requirements and Installation Steps

#### Unbound Installation
```
#sudo apt-get update
#sudo apt-get install unbound
#sudo touch /etc/unbound/host_entries.conf
#sudo chmod 777 /etc/unbound/host_entries.conf
```

For detailed installation you can use this [link](https://www.linuxbabe.com/ubuntu/set-up-unbound-dns-resolver-on-ubuntu-20-04-server)

**IMPORTANT!**

Please do not forget to change your resolver configuration! Use this [solution](https://www.linuxbabe.com/ubuntu/set-up-unbound-dns-resolver-on-ubuntu-20-04-server#:~:text=Step%203%3A%20Setting%20the%20Default%20DNS%20Resolver%20on%20Ubuntu%2022.04/20.04%20Server)

#### Apache, PHP, Mysql Installation
```
#sudo apt-get install git
#sudo apt install software-properties-common
#sudo add-apt-repository ppa:ondrej/php
#sudo apt-get install apache2
#sudo apt-get install php8.2 php8.2-common php8.2-mysql php8.2-cli
#sudo apt-get install mariadb-server
#sudo mysql_secure_installation
```

#### Permission Requirement

```
#sudo nano /etc/apache2/envvars

change these lines with your own user

export APACHE_RUN_USER=YOUR_USER
export APACHE_RUN_GROUP=YOUR_USER

save and exit.

After that you need to add your user to sudoers file,
example:
#sudo visudo
add this option under %sudo   ALL=(ALL:ALL) ALL
%YOUR_USER   ALL=(ALL) NOPASSWD:ALL

save and exit.

This is for sed commands and service reload operations.
```
For loggin permission you can solve via this [link](https://b4d.sablun.org/blog/2018-09-27-when-unbound-wont-write-logs/)

#### Database Configuration
```
#sudo mysql -u root -p
#Mysql> create database unbound;
#Mysql> create user 'dns_user'@'localhost' identified by 'Unb0undP@ss23';
#Mysql> grant all privileges on unbound.* to 'dns_user'@'localhost';
#Mysql> flush privileges;
#Mysql> use unbound;
#Mysql> create table users(
   id INT NOT NULL AUTO_INCREMENT,
   username VARCHAR(100) NOT NULL,
   password VARCHAR(255) NOT NULL);
#Mysql> insert into users (id, username, password) values (1,'dnsadmin', '$2y$10$Ud3yKKJZ8iYPXoDsiSBGE.ONBztPWF6EEUEbnNSaaPheEjWqAytRy');
```

Set your mysql root password and type Y for all questions.

#### Application Installation
```
#cd /tmp
#git clone https://github.com/kdrypr/Unbound-Web-Interface.git
#sudo mkdir /var/www/unbound
#cd Unbound-Web-Interface
#sudo mv * /var/www/unbound/
#sudo rm /var/www/unbound/README.md
#sudo chown www-data:www-data /var/www/unbound/
#sudo mv /var/www/unbound/unbound.conf /etc/unbound/unbound.conf
#sudo chown root:root /etc/unbound/unbound.conf
```

#### Features
* Add New DNS Record ( A and MX )
* Edit DNS Record
* Remove DNS Record
* Reload Unbound DNS Server

#### Screenshots
![Main Screen](https://i.hizliresim.com/kltt7o7.jpg)

![Add New Record](https://i.hizliresim.com/lewh2jc.jpg)

![Apply Changes](https://i.hizliresim.com/57sm5g4.jpg)

![Record Edit](https://i.hizliresim.com/17ivoz0.jpg)

![Record Remove](https://user-images.githubusercontent.com/19524941/211917892-669800df-60f2-42b3-8906-fbca78a1dfd7.png)

![Record Remove](https://user-images.githubusercontent.com/19524941/211918089-95440440-a520-48c9-a61c-3abfb1c55961.png)



#### Support Team
* [Faik COŞKUN](https://github.com/faikcoskun)
* [Merve Latife YAPAR](https://github.com/mrvsay)

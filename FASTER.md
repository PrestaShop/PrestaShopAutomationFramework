<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Speeding up the functional tests](#speeding-up-the-functional-tests)
- [Apache vhost in RAM](#apache-vhost-in-ram)
  - [Ramdisk setup](#ramdisk-setup)
  - [Apache setup](#apache-setup)
- [MySQL datadir in RAM](#mysql-datadir-in-ram)
  - [Ramdisk setup](#ramdisk-setup-1)
  - [MySQL setup](#mysql-setup)
    - [Things to do once](#things-to-do-once)
    - [Things to do after each boot](#things-to-do-after-each-boot)
- [HHVM ?](#hhvm-)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Speeding up the functional tests
=============================

Or how I learned to stop worrying and love installing PrestaShop.

Functional tests are slow by nature (need to launch a browser, setup a complex application state...).

To speed things up, several optimizations can be made.

We list the ones we found effective here.

The tips we outline here are based on a standard, recent Ubuntu installation (14.04 at the time of writing).

With Apache vhost and mysql datadir in ram, we get an overall x10 speed boost on stock hardware (4 core CPU, 8Go RAM).

# Apache vhost in RAM

## Ramdisk setup

In `/etc/fstab`:

```
tmpfs	/www	tmpfs	rw,nosuid,nodev,size=1G	0	0
```

## Apache setup

In `/etc/apache2/sites-enabled/fa.st.conf`:

```
<VirtualHost *:80>
	ServerName fa.st
	DocumentRoot /www/
	<Directory /www>
		Options +Indexes	
		AllowOverride All
		Require all granted
	</Directory>
</VirtualHost>
```

In `/etc/hosts`:

```
127.0.0.1 fa.st
```

# MySQL datadir in RAM

## Ramdisk setup

In `/etc/fstab`:
```
tmpfs	/mysql	tmpfs	rw,nosuid,nodev,size=2G	0	0
```

## MySQL setup

### Things to do once

In `/etc/mysql/my.cnf`:

```
[mysqld2]
datadir			= /mysql
port			= 3307
socket			= /var/run/mysqld/mysqld2.sock
pid-file		= /var/run/mysqld/mysqld2.pid
log_error		= /var/log/mysql/error2.log
```

In `/etc/apparmor.d/usr.sbin.mysqld`, add to the list of permissions:

```
/var/run/mysqld/mysqld2.pid rw,
/run/mysqld/mysqld2.pid rw,
/var/run/mysqld/mysqld2.sock w,
/run/mysqld/mysqld2.sock w,
/mysql/** rwk
```

Then run:
```bash
sudo service apparmor restart
```

### Things to do after each boot

```bash
sudo mysql_install_db --datadir="/mysql"
sudo mysqld_multi start 2
```

# HHVM ?

As of right now, PrestaShop installs correctly on HHVM, but some tests make the HHVM server crash, so right now it is not such a good idea.

We leave this here for further consideration, as HHVM is likely to give a big performance boost.

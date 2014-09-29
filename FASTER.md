Speeding up the functional tests
=============================

Or how I learned to stop worrying and love installing PrestaShop.

Functional tests are slow by nature (need to launch a browser, setup a complex application state...).

To speed this up, several optimizations can be made.

We list the ones we found effective here.

# Apache VHOST in RAM

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
```

### Things to do after each boot

```bash
sudo mysql_install_db --datadir="/mysql"
sudo mysqld_multi start 2
```
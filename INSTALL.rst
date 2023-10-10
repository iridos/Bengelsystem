Installation:
-------------

LAMP
....


Install a "LAMP" server via your distribution:

* web server 

  + with php capabilities

* sql database (mariadb)

* email server/ability to send emails (optional)


You also need: 

* jquery

* dhtmlxscheduler


JS libraries
............


.. code-block:: bash

   cd html/js
   wget https://code.jquery.com/jquery-3.7.1.min.js
   cd ..
   wget https://github.com/DHTMLX/scheduler/archive/refs/tags/v6.0.5.tar.gz
   tar -xvf v6.0.5.tar.gz
   ln -s  scheduler-6.0.5 scheduler
   rm v6.0.5.tar.gz


Example for debian: 
...................


webserver:

.. code-block:: bash

   apt-get install apache2 openssl python3-certbot-apache php-mysql php-json

sql database:

.. code-block:: bash

   apt-get install mariadb-server mariadb-client

Copy the contents of the html directory into your webserver tree.

Create database and database user.

Restore database from backup:

TODO

Edit konfiguration.php and adapt to your installation

Login with admin user: admin@admin.de pw: 4024ae463a4ef9ff455705afe54f83c3


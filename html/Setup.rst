
HelferDB Setup Wizard
---------------------

Diese Seite führt durch die Einrichtung der Helferdatenbank.

.. raw:: html

   <?php
   if(!isset($_GET['step'])){
   ?>


Schritt 1: Logindaten für Datenbank eingeben
............................................

Bitte hier zunächst die Logindaten für die Datenbank eingeben. Es muss in der Regel nur ein Passwort vergeben werden und die anderen beiden Werte können unverändert bleiben.

.. raw:: html

   <form action="Setup.php" >
       <input type="hidden" name="step" value="2" />
       <label for="host">Hostname</label><br />
       <input type="text" id="host" name="host" value="localhost" /><br />
       <label for="user">Benutzername</label><br />
       <input type="text" id="user" name="user" value="helferdb" /><br />
       <label for="password">Passwort</label><br />
       <input type="password" id="password" name="password" /><br />
       <input type="submit" value="Schritt 2: Datenbank einrichten" />
   </form>
   <?php } ?>


  
.. raw:: html

   <?php
   if(isset($_GET['step']) && $_GET['step'] == '2'){ 
   ?>


Schritt 2: Datenbank einrichten
...............................

Bitte zunächst mariadb einrichten (oder mysql) und dazu folgendes in der Shell (z.b. bash) eingeben:

.. code:: bash

   sudo apt-get install mariadb


.. raw:: html

   <?php } ?>

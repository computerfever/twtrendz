# Environment preparation

1. Set up a connection in config/sharding.php
2. Execute the following command to set up the userdb:init [connection]

# Migration

1. Choose a customer, specified by [customer_id]

2. [Optional] Dump the customer's data in the Master DB

   php artisan userdb:dump [customer_id] [dumpfile]

3. [Optional] Import the customer's data from dumpfile to the intended server's connection

   php artisan userdb:import [connection] [dumpfile]

4. Clone the customer profile from Master DB to the user DB

   php artisan userdb:sync [customer_id]

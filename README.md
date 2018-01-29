# csv-seeder

Seed db tables with .csv files across different environments and on different db connections.

### Reqs
#### Directory Structure
/database/seeds/{environment}/{database connection}/{filename}.csv

#### Filenames
Your .csv files must be named like a Laravel migration file: YmdHis-table_name.csv
Example: 20170107083000-users.csv 

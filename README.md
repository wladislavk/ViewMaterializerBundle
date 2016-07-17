About
=====

A materialized view is a table that does not contain any original data - any of its data
comes from a SELECT query on some other tables. Unlike simple views, materialized views
are stored on disk as simple tables. Any query on a materialized view works much faster
than on a simple view, however, materialized views need to be regularly updated to
reflect changes in their underlying tables. Some DB engines have default support for
view materialization, but MySQL does not.

This is a simple bundle that was created to emulate materialized views in MySQL. It depends
on Doctrine and is available both as stand-alone service and console command that can be
launched via Cron. It also depends on VKRCustomLoggerBundle.

Currently, this bundle does not support multiple DB connections and will always use default
connection.

Installation
============

Besides enabling the bundle in your ```AppKernel.php```, you must configure it and create
a log file.

The log file needs to be placed into ```/app/logs/```, it should have ```.log``` extension
and, of course, it should be open for writing.

To configure the bundle, create ```vkr_view_materializer``` key in your ```config.yml```
or in any other included configuration file. Under this key, you need two keys: ```log_file```
contains your log file name without path and extension. ```views``` key contains the main
bulk of configuration, namely - a dictionary with keys that correspond to materialized
view names and values that are SELECT queries that create these views.

Example in YAML:

```
vkr_view_materializer:
    log_file: view_materializer
    views:
        first_mview: "SELECT a from table1 WHERE b=c"
```

Usage
=====

There are two ways to use this bundle - from a controller or from console.

If you use it from the console, enter ```php app/console views:materialize```. If your
configuration is as in the example above, Doctrine will attempt to make these queries:

```
DROP TABLE IF EXISTS first_mview;
CREATE TABLE first_mview AS SELECT a from table1 WHERE b=c;
```

If you are using Symfony 3, swap ```app/console``` for ```bin/console```.

If there are any errors, they will be output to your log file.

From the controller, you need to call:

```
$materializer = $this->get('vkr_view_materializer.view_materializer');
$isSuccessful = $materializer->materializeViews();
```

If there are any errors, ```false``` will be returned and the errors logged to the file.

API
===

*void ViewMaterializer::__construct(Doctrine\ORM\EntityManager $em, VKR\CustomLoggerBundle\Services\CustomLogger $logger, string[] $definitions, string $logFile)*

*bool ViewMaterializer::materializeViews()*

In case of an error while executing a query, execution will be immediately stopped and
false returned. Otherwise, returns true.

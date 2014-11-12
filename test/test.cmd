@echo off

rem http://stackoverflow.com/questions/1414125/how-do-i-run-all-my-phpunit-tests
rem put phpunit.xml into root directory to run 
rem the tests located in Tests/
rem
rem The autoload.php isregisterd in phpunit.xml
rem http://jes.st/2011/phpunit-bootstrap-and-autoloading-classes/
rem 
phpunit --verbose --configuration "phpunit.xml"

rem will run all scripts named xxxxTest.php in directory
rem --bootstrap <file> A "bootstrap" PHP file that is run before the tests.

rem phpunit --bootstrap "autoload.php" "Molengo/Test"

pause
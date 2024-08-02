# Change Log
All notable changes to this project will be documented in this file.

### UNRELEASED

## Release 1.4
- NEW : COMPAT V20 - *25/07/2024* - 1.4.0

## Release 1.3
- NEW : TachATM + Page About *10/01/2024* - 1.3.0
- NEW :   Changed Dolibarr compatibility range to 12 min - 19 max   	- *04/12/2023* - 1.2.0
          Changed PHP compatibility range to 7.0 min - 8.2 max		- *04/12/2023* - 1.2.0

## Release 1.0
- FIX : Compatibility v17 / PHP 8 - interface.php add NOCSRFCHECK - *31/01/2023* - 1.1.11
- FIX : Missing icon - *17/10/2022* - 1.1.10 
- FIX : PHP 8 - *19/08/2022* - 1.1.9
- FIX : `Interface.php` has fatal errors (invisible to user) due to SQL
  injection of empty input values - *29/06/2022* - 1.1.8
- FIX: Compatibility V16 - token, _update trigger and family - *30/06/2022* - 1.1.7
- FIX : Can't create more product prices if multidevise is enable - *01/06/2022* - 1.1.6
- FIX : UX Changes between DOL 13.0 and 14.0 so we pull the qsp form under addline tpl - *02/05/2022* - 1.1.5
- FIX : tvatx must not be converted to int, because it can have decimals and specific tva code - *30/03/2022* - 1.1.4
- FIX : Fill the unit price to be used by the addline action of fourn/commande/card.php which has changed between V12 and V13 - *22/12/2021* - 1.1.3
- FIX : Compatibility V13 - Add token renewal - *18/05/2021* - 1.1.2
- FIX [2020-12-10] Fetch and display the OF select value when link an OF on CF (OF select on Dolibarr form AND OF select on Quicksupplier form)

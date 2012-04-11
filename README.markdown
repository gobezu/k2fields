k2fields
========
k2fields is a set of extensions primarily providing you:

* additional set of field types defined through a comprehensive field editor. Currently available field types are:
    - list: database table mapped nested set based hierarchical values (refer to wiki page)
    - media: using as source upload files (including archive uploads) or from media providers such as youtube, and displaying using any of the many compatible media plugins you have installed
    - date/time: date, datetime, duration with ability to expire K2 items on the basis of field values and repetition of dates with designated interval until reached given date/time or for a number of repetitions
    - email: with abilility to connect to forms (currently working with fabrik)
    - basic: range (numeric range), days, verifybox, yesno, numeric, alpha, alphanumeric, text
    - k2item: embedding other K2 items within a K2 item
    - complex: composition of other defined fields
* advanced search capability
* content module with numerous item selection and styling varieties
* various predefined item/itemlist layouts
* rating / review based on jcomments with ability to define varying review criterias for various K2 categories (refer to wiki page)
* microdata support based on [schema.org](http://schema.org) definitions
* and much more...

Requirements
------------
* Joomla! version 2.5.4+
* K2 version 2.5.6+

Usage
-----
Refer to the wiki [https://github.com/gobezu/k2fields/wiki](https://github.com/gobezu/k2fields/wiki)

Support
-------
* Discussion forum [https://groups.google.com/forum/#!forum/k2fields](https://groups.google.com/forum/#!forum/k2fields)
* Specific issue to report - please do make entry in the issue tracker [https://github.com/gobezu/k2fields/issues](https://github.com/gobezu/k2fields/issues)
* Specific support request - submit it [http://jproven.com/k2fields/contact](http://jproven.com/k2fields/contact)

Notes (To be expanded...)
-----
* k2fields works in some parts thanks to overrides. In those cases where certain extensions hinder such override to take place by directly requiring or including then k2fields might fail to perform but please don't hesitate to raise such issues...
* Plain K2 fields doesn't work well when k2fields is activated so you would need to choose either or...
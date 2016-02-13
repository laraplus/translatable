<?php

return [

   /*
   |-----------------------------------------------------------
   | Database schema conventions
   |-----------------------------------------------------------
   |
   | Translatable tables can follow any convention you like,
   | so here we let you set the translation table suffix,
   | as well as the name of the locale database column.
   |
   */
    'db_settings' => [
        'table_suffix' => '_i18n',
        'locale_field' => 'locale'
    ],


   /*
   |-----------------------------------------------------------
   | Default query behavior
   |-----------------------------------------------------------
   |
   | When fetching records, you have an option to only select
   | translated results or even fallback to another locale.
   | You may configure the default query behavior below.
   |
   */
    'defaults' => [
        'only_translated' => false,
        'with_fallback'   => true,
    ],

];
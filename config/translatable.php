<?php

return [

    /*
   |--------------------------------------------------------------------------
   | Translation table columns
   |--------------------------------------------------------------------------
   |
   | Translatable tables can follow any convention you like,
   | so here we let you set the translation table suffix,
   | and locale key. Your migrations should follow that.
   |
   */
    'db_settings' => [
        'table_suffix' => '_i18n',
        'locale_field' => 'locale'
    ],

    'defaults' => [
        'only_translated' => false,
        'with_fallback' => true,
    ],

];
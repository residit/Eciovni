# Eciovni
Component for generating invoices using Nette Framework and mPDF library.

For of fork of original library. I create this fork to test if it works with PHP 7.0 and mPDF 6.1.

Install via composer:

Edit composer.json.

```
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/lamagoci/Eciovni"
        }
        
    ...
    
    "require": {
        ...
        "mpdf/mpdf": "^6.1",
        "ondrejbrejla/eciovni": "v2.1b"
```

For install all dopendecies just run
```
composer update
```

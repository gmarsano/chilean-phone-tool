<h1 align="center">🇨🇱 Chilean Phone Tool ☎️</h1>

## Introduction

A PHP composer package to work with Chilean 🇨🇱 phone numbers.

### Features
- Parsing
- Get prefix
- Validation
- Formatting
- Factory

### Contents
  - [Introduction](#introduction)
    - [Features](#features)
    - [Contents](#contents)
  - [Install](#install)
    - [Requirements](#requirements)
  - [Usage](#usage)
    - [Set a phone number.](#set-a-phone-number)
      - [`parse`](#parse)
      - [`setPhone`](#setphone)
    - [Validation](#validation)
      - [`validate`](#validate)
      - [`errors` and `messages`](#errors-and-messages)
        - [Error messages](#error-messages)
      - [Ignore prefix validation](#ignore-prefix-validation)
      - [`fix` and `luckyFix`](#fix-and-luckyfix)
    - [Formatting](#formatting)
      - [`format`](#format)
      - [Choosing format](#choosing-format)
    - [Factory](#factory)
    - [Aliases](#aliases)
  - [Inspired by](#inspired-by)
  - [License](#license)

## Install

```sh
composer require gmarsano/chilean-phone-tool
```

### Requirements
- PHP >= 7.3

## Usage

```php
use Gmarsano\ChileanPhoneTool\Phone;

$phone = Phone::parse("+56 9 87-654-321");
$phone->validate();   // true
$phone->number();     // "987654321"
$phone->fullNumber(); // "56987654321"
$phone->prefix()      // "9"
$phone->format();     // "+56 9 87-654-321"
```
### Set a phone number.

#### `parse`
Usually you may want to take a phone in any format, check if it's valid and
bring it to the desired format. Use `parse` to set the number, if that the case.

If you parse a valid number you can get the number and prefix just calling
these:
```php
Phone::parse("+56 9 87-654-321")->number();
// => "987654321"

Phone::parse("+56-32-7-654-321")->prefix();
// => "32"
```

Clean an invalid input (use `quiet` to prevent validation exceptions):
```php
Phone::parse("2 (09) 987654321")->quiet()->get();
// => "209987654321"

// yes work with integer or float, too.
Phone::parse(1.23)->quiet()->number();
// => "123"
```

#### `setPhone`
Sometimes you just want to know if an input value is a valid phone number
without clean or make any changes on it before validation. It's time to use
`setPhone`:

```php
Phone::setPhone("56987654321")->quiet()->validate();
// => true

Phone::setPhone("987654321")->quiet()->validate();
// => true

Phone::setPhone("+56 9 87-654-321")->quiet()->validate();
// => false
```

### Validation

#### `validate`
Use `validate` to check if it is a valid number. An invalid number will throw an
exception with a message giving information about the reasons.

```php
Phone::parse("+56-32-7-654-321")->validate();
// => true

Phone::parse("+56 1 87-654-321")->validate();
// Exception with message 'Invalid prefix.'
```

The features of this tool make sense on a valid phone. This is why by default it
works with exceptions. But you can use `quiet` to avoid this behavior, but be
careful, if you try to get or format an invalid number you will get the original
digits or value depending on the case.

#### `errors` and `messages`
They allow to obtain an array with validation messages if `quiet` has been used.
They may look like aliases, but there is a little difference:
- with `errors` you get a list of the validation messages with error codes as
keys.
- with `messages` you only get the messages.

```php
$phone = Phone::parse("+56 1 87-654-321");
$phone->quiet()->validate();
// => false

$phone->errors()
/*
=> [
     5 => "Invalid prefix.",
   ]
*/

$phone->messages()
/*
=> [
     "Invalid prefix.",
   ]
*/
```
##### Error messages
|code |<div align="center">message</div>|
|:---:|---------------------------------|
|2    |Empty digits count.              |
|3    |Invalid phone number format.     |
|4    |Invalid country code.            |
|5    |Invalid prefix.                  |
|6    |Invalid phone number.            |

#### Ignore prefix validation
To avoid prefix validation use `ignorePrefix`:
```php
$phone = Phone::parse("+56 1 87-654-321");
$phone->quiet()->validate();
// => false

$phone = Phone::parse("+56 1 87-654-321");
$phone->ignorePrefix()->validate();
// => true

$phone->get()
// => "187654321"

$phone->prefix()
// => "18"
```

#### `fix` and `luckyFix`
Tries to set a valid phone from original input.
```php
$phone = Phone::setPhone("+56 032 7-654-321");
$phone->quiet()->validate();
// => false

$phone->fix()->validate();
// => true

$phone->get();
// => "327654321"
```

Use `getOld` to get original value.
```php
$phone->getOld();
// => "+56 032 7-654-321"
```

Sometimes the inputs may lack the Santiago prefix (2). Use `luckFix` to fix and
try to guess if the missing prefix can be fixed.
```php
$phone = Phone::parse("+56-37-654-321");
$phone->quiet()->validate();
// => false

$phone->luckyFix()->validate();
// => true

$phone->format();
// => "+56 2 37-654-321"

$phone->errors();
/*
=> [
     3 => "Invalid phone number format.",
   ]
*/
```

As you can see, after fix, validation can be true. If you need to check if
original was modified because it was invalid, then you can count errors.

### Formatting

#### `format`
Use `format` on a valid phone to get the value in standard numbering format.
```php
$phone = Phone::setPhone("987654321");
$phone->quiet()->validate();
// => true

$phone->format();
// => "+56 9 87-654-321"
```

#### Choosing format
Give `format` method the desired format as an argument.

|<div align="center">format</div>         |<div align="center">example</div>  |
|-----------------------------------------|-----------------------------------|
|FormatterInterface::STANDARD_FORMAT      |+56 9 87-654-321, +56 75 7-654-321 |
|FormatterInterface::PREFIX_FORMAT        |(9) 87-654-321, (75) 7-654-321     |
|FormatterInterface::DIGITS_FORMAT        |56987654321, 56757654321           |
|FormatterInterface::NUMBER_DIGITS_FORMAT |987654321, 757654321               |

```php
use Gmarsano\ChileanPhoneTool\Contracts\FormatterInterface;

Phone::setPhone("987654321")->quiet()
  ->format(FormatterInterface::PREFIX_FORMAT);
// => "(9) 87-654-321"
```

### Factory
You can generate a valid phone number using Phone Tool:
```php
Phone::factory()->make()->first();
// => "451036552"
```

Make multiple numbers giving a count as argument to `make` method.
```php
Phone::factory()->make(5)->all();
/*
=> [
     "523677441",
     "243584227",
     "712584943",
     "671108073",
     "633943870",
   ]
*/
```

You can use the `unique`, `cellPhone`, `landLine` or `prefix` modifiers to make
unique numbers, cell phone, landline (red fija), or set a valid prefix manually.
```php
Phone::factory()->cellPhone()->make()->first();
// => "973986533"

Phone::factory()->unique()->landLine()->make(3)->all();
/*
=> [
     "649800571",
     "572435282",
     "805066069",
   ]
*/

Phone::factory()->unique()->prefix(2)->make(3)->all();
/*
=> [
     "249404634",
     "250034633",
     "243960969",
   ]
*/
```

Use `format` (check [choosing format](#choosing-format)) modifier to give
desired format to numbers.
```php
Phone::factory()->make()->format()->first();
// => "+56 68 4-987-466"

Phone::factory()->unique()->make(3)
  ->format(FormatterInterface::PREFIX_FORMAT)
  ->all();
/*
=> [
     "(9) 76-488-717",
     "(45) 1-806-739",
     "(42) 1-444-163",
   ]
*/

// try this if you quickly need digits with country code
Phone::factory()->make(1, true)->first();
// => "56555182350"
```

### Aliases

|methods|aliases|
|:----:|:---:|
|get()|number|
|get(true)|fullNumber|
|getPrefix|prefix|
|validate|isValid|

## Inspired by
[Freshworks Chilean Bundle](https://github.com/freshworkstudio/ChileanBundle)


## License
MIT License  
Copyright (c) 2021 gmarsano

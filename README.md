## Synopsis

[See a demo here](https://www.jcdh.jrquick.com)

* Visit [my website](https://jrquick.com) for me cool stuff!

## Code Examples

##### Create Instance of API Wrapper
```php
$api = new JcdhApi(JcdhOutput::JSON);
```

##### Get food scores
```php
$results = $api->getScores(JcdhTypes:FOOD);
```

```php
$results = $api->getFoodScores(JcdhTypes:FOOD);
```

##### Get food scores starting with A, B, or C
```php
$results = $api->getFoodScores('ABC');
```

##### Get multiple types of scores
```php
$results = $api->getScores("${JcdhTypes:COMMUNAL_LIVING},${JcdhTypes:FOOD},${JcdhTypes:POOL}");
```

## Output Examples

##### Communal Living Output
```json
[
  {
    "score":     "88",
    "date":      "04/11/1990",
    "name":      "Artificial Homes",
    "address":   "1300 Mardis Drive BIRMINGHAM 35235",
    "location": {
      "lat": 22.1414424,
      "lng": -44.4124124
    },
    "deductions": [
      // See deduction output example
    ]
  }
]
```

##### Food Output
```json
[
  {
    "permit_no":  "615",
    "score":      "96",
    "name":       "Fake Cinemas",
    "date":       "04/11/1990",
    "address":    "1303 Decatur HWY GARDENDALE 35071",
    "smoke_free": true,
    "location": {
      "lat": 33.6579989,
      "lng": -86.8112498
    },
    "deductions": [
      // See deduction output example
    ]
  }
]
```

##### Hotel Output
```json
[
  {
    "score":                "53",
    "name":                 "Not Real, But Really Crummy Inne",
    "date":                 "04/11/1990",
    "address":              "12 19th St S BIRMINGHAM 35205",
    "establishment_number": "5245235",
    "number_of_units":      "13",
    "location": {
      "lat": -33.424242,
      "lng": 53.525525
    },
    "deductions": [
      // See deduction output example
    ]
  }
]
```

##### Pool Output
```json
[
  {
    "score":     "88",
    "date":      "04/11/1990",
    "name":      "Pretend Pool Party",
    "address":   "1212 Taco Bell DR CLAY 35235",
    "type":      "public",
    "location": {
      "lat": 24.435252,
      "lng": 55.352552
    },
    "deductions": [
      // See deduction output example
    ]
  }
]
```

##### Pool Output
```json
[
  {
    "score":     "88",
    "date":      "04/11/1990",
    "name":      "Fake Orange Tanners",
    "address":   "1600 Pennsylvania AVE CLAY 35235",
    "permit_no": "232",
    "location": {
      "lat": -55.435252,
      "lng": 55.352552
    },
    "deductions": [
      // See deduction output example
    ]
  }
]
```

###### Deduction/Reports Output 
```json
{
    "value":              "4",
    "compliance_number":  "2-102.11(B)",
    "compliance_details": "Keep food-contact surfaces of cooking equipment (grills, fryers, etc.) and pans free of encrusted grease deposits and other soil accumulations.",
    "notes":              "Popcorn lid inside popcorn popper has heavy grease residue."
  }
```

## Motivation

I made this project out of personal curiosity, feel free to contribute or send me ideas/improvements (though I cannot promise I will get to it ~~very quickly~~ ever).

## Installation

// TODO

## API Reference

// TODO

## Tests

I know, I know. I will get to it _eventually_.

## Contributors

I currently maintain this project alone, you can find more of my projects and contact me on [my website](https://www.jrquick.com). If you would like to be a contributor then please do!

## License

MIT License

>Copyright (c) 2018 Jeremy Quick

>Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

>The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Upcoming

Send me ideas!

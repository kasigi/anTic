[![Stories in Ready](https://badge.waffle.io/kasigi/anTicketer.png?label=ready&title=Ready)](https://waffle.io/kasigi/anTicketer)
# anTic, the Ticketing System and General CMS
This is a php + angular application that can be used to track issues in a project.

This is not intended as a fully supported long-term project but a one-off creation with two goals:
<ol>
<li>Continue to Learn Angular</li>
<li>Have an internal task tracking tool</li>
</ol>

## Rerquirements

* MySQL 5+ (Tested against 5.6)
* PHP 5.5+
* Apache 2.2+

## Installation

1. Upload the source code to the server / desired folder
2. Copy the systemSettings-sample.php to systemSettings.php
3. Update the database connection information
4. Create the data model files in dataModelMeta/data

## Data Models

The anTic CMS builds edit interfaces based on the models specified in the dataModelMeta/data/ folder. Each file must be named EXACTLY like the matching table in MySQL plus the extension .json. *The existence of a correctly named file is adequate for the engine to build the table and edit interface.* Additional hints and descriptions of more complicated models may be added to the json file.

### Complete model sample
This is a sample of every possible field in the model. These are *ALL* optional. Specify them or not as desired.
<pre>
{
    "displayName":"",
    "description":"",
    "fields":{
      "fieldName":{
        "helpText":"",
        "displayName":"SampleDisplayName",
        "foreignKeyDisplayFields":["fkField1","fkField2"],
        "fieldEditDisplayType":"email",
        "fieldValidationRegex":"/#?[a-fA-F]{6}/"
      }
    },
    "listViewDisplayFields": ["person","status","projectTypeID","title"]
  }
}
</pre>

<dl>
<dt>displayName</dt>
    <dd>This is the table's name displayed to users in selects and various titles.</dd>
<dt>description</dt>
    <dd>This is a text description of the table's content/purpose.</dd>
<dt>fields</dt>
    <dd>This is an array (object) of all fields that require additional meta data.</dd>
<dt>fields:fieldName:helpText</dt>
    <dd>This is the tooltip help bubble shown next to the field's display name</dd>
<dt>fields:fieldName:foreignKeyDisplayFields</dt>
    <dd>If the field is a foreign key, fields from that table can be shown to the user instead of the raw value in the interface. This is an array.</dd>
<dt>fields:fieldName:fieldEditDisplayType</dt>
    <dd>This allows special edit inputs to be specified for the user interface. Current valid options are listed in dataModelMeta/validDAtaTypesMap.json.</dd>
<dt>fields:fieldName:fieldValidationRegex</dt>
    <dd>This is a custom field validation regex string. If the input does not match, then the field will return invalid.</dd>
<dt>listViewDisplayFields</dt>
    <dd>The list/table view that shows many records can be constrained to only show certain fields. Note: it is NOT possible to suppress primary key fields and they will be forcibly displayed.</dd>

</dl>

## Known Limitations

The system is able to support foreign keys and tables both with and without primary keys.  HOWEVER, support for multi-field primary keys and multi-field foreign keys is limited and untested. There are also some upper limits on how much data can be stored in tables that do not have primary keys. It is STRONGLY recommended to have primary keys in wherever possible.

## Important Note

Note: I'm using this tool BEHIND firewalls and with a server that provides authentication before the site is even reached.  Do NOT deploy this tool directly on public-facing IP's without adding a layer of security on top.  This is largely a learning tool and is not intended for production.


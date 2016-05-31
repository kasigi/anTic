[![Stories in Ready](https://badge.waffle.io/kasigi/anTicketer.png?label=ready&title=Ready)](https://waffle.io/kasigi/anTicketer)
# anTic, the Ticketing System and General CMS
This is a php + angular application that can be used to track issues in a project.

This is not intended as a fully supported long-term project but a one-off creation with two goals:
<ol>
<li>Continue to Learn Angular</li>
<li>Have an internal task tracking tool</li>
</ol>

## Known Limitations

The system is able to support foreign keys and tables both with and without primary keys.  HOWEVER, support for multi-field primary keys and multi-field foreign keys is limited and untested. There are also some upper limits on how much data can be stored in tables that do not have primary keys. It is STRONGLY recommended to have primary keys in wherever possible.

## Important Note

Note: I'm using this tool BEHIND firewalls and with a server that provides authentication before the site is even reached.  Do NOT deploy this tool directly on public-facing IP's without adding a layer of security on top.  This is largely a learning tool and is not intended for production.


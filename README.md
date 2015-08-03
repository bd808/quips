Quips server
============

A bash.org inspired quips server.

When [Wikimedia][] used [Bugzilla][] as its issue tracker, we also used the
quips functionality to store funny quotes from irc, bug reports, code reviews
and other sources. Since we have moved to [Phabricator][], this functionality
has been missed ([T73245][]). This app attempts to bring joy back to the
masses by allowing access to the former quips dataset that is now stored in an
[Elasticsearch][] index.


Wikimedia Tool Labs
-------------------

This service is currently running in [Wikimedia Tool Labs][] as the [bash][]
tool. It uses an Elasticsearch server hosted in the [stashbot][] project of
[Wikimedia Labs][]. The stashbot project uses a [Logstash][] server and its
[irc input plugin][] to collect messages from various IRC channels. The
Logstash instance uses some custom rules to look for messages in the IRC
channels that start with `!bash` and adds them to a special Elasticsearch
index. That same index was bootstrapped with a [list of quips][] that were
salvaged from the former bugzilla.wikimedia.org server.


Credits
-------
Favicon from http://glyphicons.com/ (CC-BY 3.0)


License
-------
[GNU GPLv3+](//www.gnu.org/copyleft/gpl.html "GNU GPLv3+")


---
[Wikimedia]: https://wikimediafoundation.org/wiki/Home
[Bugzilla]: https://www.bugzilla.org/
[Phabricator]: http://phabricator.org/
[T73245]: https://phabricator.wikimedia.org/T73245
[Elasticsearch]: https://www.elastic.co/products/elasticsearch
[Wikimedia Tool Labs]: https://wikitech.wikimedia.org/wiki/Help:Tool_Labs
[bash]: https://tools.wmflabs.org/bash
[stashbot]: https://wikitech.wikimedia.org/wiki/Nova_Resource:Stashbot
[Wikimedia Labs]: https://wikitech.wikimedia.org/wiki/Help:FAQ
[Logstash]: https://www.elastic.co/products/logstash
[irc input plugin]: https://www.elastic.co/guide/en/logstash/current/plugins-inputs-irc.html
[list of quips]: https://phabricator.wikimedia.org/P110

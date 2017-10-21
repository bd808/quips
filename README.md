Quips server
============

A bash.org inspired quips server.

When [Wikimedia][] used [Bugzilla][] as its issue tracker, we also used the
quips functionality to store funny quotes from irc, bug reports, code reviews
and other sources. Since we have moved to [Phabricator][], this functionality
has been missed ([T73245][]). This app attempts to bring joy back to the
masses by allowing access to the former quips dataset that is now stored in an
[Elasticsearch][] index.


Wikimedia Toolforge
-------------------

This service is currently running in [Wikimedia Toolforge][] as the [bash][]
tool. It uses an [Elasticsearch][] index maintained by [stashbot][] and hosted
in Toolforge. Stashbot collects messages from various IRC channels and looks
for messages that start with `!bash`. The index was bootstrapped with a
[list of quips][] that were salvaged from the former bugzilla.wikimedia.org
server.


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
[Wikimedia Toolforge]: https://wikitech.wikimedia.org/wiki/Help:Toolforge
[bash]: https://tools.wmflabs.org/bash
[stashbot]: https://github.com/bd808/tools-stashbot
[list of quips]: https://phabricator.wikimedia.org/P110

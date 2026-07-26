# µADR-039: Source-Text JSON Internationalization & Security-Gated Hydration
-----
tags: #i18n #json #opcache #security #js-hydration
status: accepted
context: Traditional i18n architectures introduce heavy parser overhead or abstract key maintenance. Exposing all translations to frontend JavaScript risks leaking protected endpoint terminology.
decision:
  - Source text in code acts directly as the translation lookup key with fallback support.
  - JSON translation files are cached via production OPcache PHP array compilation for zero runtime overhead.
  - JavaScript client translation payloads are security-gated: public routes export public strings only, while protected component strings require authenticated contexts.
consequences:
  - High performance, clean DX with named argument interpolation, and decoupled extension boundaries.

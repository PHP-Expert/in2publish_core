# in2publish_core - Content publisher for TYPO3

[![Latest Stable Version](https://poser.pugx.org/in2code/in2publish_core/v/stable)](https://packagist.org/packages/in2code/in2publish_core) [![Build Status](https://travis-ci.org/in2code-de/in2publish_core.svg?branch=master)](https://travis-ci.org/in2code-de/in2publish_core) [![Latest Unstable Version](https://poser.pugx.org/in2code/in2publish_core/v/unstable)](https://packagist.org/packages/in2code/in2publish_core) [![License](https://poser.pugx.org/in2code/in2publish_core/license)](https://packagist.org/packages/in2code/in2publish_core) [![Codacy Badge](https://api.codacy.com/project/badge/Grade/fb1891cd41bd4b9db6a29b4b01952ec1)](https://www.codacy.com/app/vertexvaar/in2publish_core?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=in2code-de/in2publish_core&amp;utm_campaign=Badge_Grade)

## Introduction

Content Publishing in TYPO3 - the easy way:

* Intuitive Use
* High Security
* Future proof
* Supports all extensions (with correct TCA)

<img src="https://www.in2code.de/fileadmin/content/images/produkte/contentpublisher/content_publisher_screenshot01_prev.png" width="450" />

## Support
This TYPO3 Extension is free to use. We as in2code and our developers highly appreciate your feedback and work hard to improve our extensions. 
To do so, in2code provides two extra days per month for coding and developing (Coding Night and Freaky Friday). During these days our more than 20 developers spend their time with improvements and updates for this and other extensions.

You can support our work [here](https://www.in2code.de/extensionsupport).

Thank you very much in advance.

Your in2code Team


### Description

The Content Publisher takes working with content to a whole new level. As an editor you can see all changes
at a glance and publish them. Changes of major revisions can be prepared and approved individually.
Pages and related content and files are reliably transferred as well.
You can also publish selected page trees to the live server.

Editing pages can be structured into a multi-step workflow, thus editing, reviewing, and publishing can be separated into distinct roles.
The modern user interface facilitates intuitive handling without excessive training.

### Questions

If you have questions **please** have a look at the [FAQs](Documentation/FAQ.md) first. If your question is not listed try to find your answer in the documentation.
You can ask questions (no support!) in the #ext-in2publish Slack channel on typo3.slack.com but there is no warranty.
If you need support you can [contact in2code](https://www.in2code.de/en/).

### Technical note

The content publisher basically requires two TYPO3 instances. A staging and a live instance.
Editors work solely on the stage server. They also have a backend module to manage pages and files and their publishing status.
This means that backend access to the live server is not required anymore. The data transfer between
the two servers is secured by encrypted connections and allows only unidirectional system access from stage to live.

Data from the live server is only transferred upon explicit request from the stage server. This also means
that the stage server can be placed inside the private company intranet while the live server is accessible
throughout the internet. The same TYPO3 extension is installed on both servers. So both servers only differ
in configuration. This has the great advantage that an existing deployment can be used for both systems at once.

See for more details:

* https://www.in2code.de/produkte/content-publisher/ (german)
* https://www.in2code.de/fileadmin/content/downloads/in2code_content_publisher_en.pdf (english)

### Screenshots

<img src="https://www.in2code.de/fileadmin/content/images/produkte/contentpublisher/content_publisher_screenshot04_prev.png" width="600" />

Example overview module with details


<img src="https://box.everhelper.me/attachment/915974/84725fb7-0b3e-4c40-b52e-29d7620777bb/262407-BasbtZAplLd9ZICI/screen.png" width="600" />

Side by side comparison between stage and live


<img src="https://box.everhelper.me/attachment/915967/84725fb7-0b3e-4c40-b52e-29d7620777bb/262407-51SodD2DusbJ5WS4/screen.png" width="600" />

Installation support by a lot of tests


<img src="https://box.everhelper.me/attachment/915965/84725fb7-0b3e-4c40-b52e-29d7620777bb/262407-5nHfdlwJ6tLNPxBi/screen.png" width="600" />

Example workflow module (part of the enterprise version)


<img src="https://www.in2code.de/fileadmin/content/images/produkte/contentpublisher/content_publisher_screenshot03_prev.png" width="600" />

Example workflow feature (part of the enterprise version)


<img src="https://box.everhelper.me/attachment/915970/84725fb7-0b3e-4c40-b52e-29d7620777bb/262407-93UtQ9cPeb0NCY1e/screen.png" width="600" />

Browser notifications after asynchronous publishing (part of the enterprise version)

## Installation

`composer require in2code/in2publish_core`

Easy installation via composer. See documentation for a step by step manual

## Documentation

* [Requirements And Limitations](Documentation/RequirementsAndLimitations.md)
* Extension documentation: [Documentation](Documentation/README.md)
* Community help: https://typo3.slack.com/messages/ext-in2publish/

## Version changelog

See: [Changelog](CHANGELOG.md)

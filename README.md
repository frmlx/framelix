# Framelix Full-Stack PHP Framework

Framelix is a full-stack PHP framework in a nicely packed, ready to go, docker environment. It's main focus is to
provide features for agile development
of data management applications. It contains a full-featured responsive backend with a big set of features for your
daily business in data manegement development. Make your apps fast and reliable in no time.

## How to use

Doc pages are currently work in progress. Check back later.

## Main Features

* Built-In responsive backend - Don't worry about layout, build business logic.
* Full-featured form generator - Data manegement requires forms very often. We focused on that from the beginning.
* Built-In JS/SCSS compiler/bundler - Write newest JS code, automatically compiled to browser standards
* Model-View-Controller alike development - Kind of MVC but simplified and reduced to what is really required.
* Nearly 100% auto-completion support in your editor out of the box. We have some neat dev-tricks that you might have
  not known yet.
* Advanced ORM Database Features - You work with `Storables`, which are basically objects that are stored in the
  database. You can write raw queries, but it's not required 99% of the time.
* Easy routing - We call our pages `Views`. A view is basically mapped to a URL. You can map a view to any URL you like.
* Secure and Up2Date technology - As framelix is required to run in a docker container, it's really secure by default
  and you don't have to think about the requirements to run the app. It's all contained inside the container by default.
  Right now we are on PHP 8.1 and we adopt to newest PHP generation each time a new stable version comes out.

## Apps using Framelix

* [PageMyself - Fast, easy and powerful website generator](https://github.com/NullixAT/pagemyself)
* [Buhax - Accounting software for small companies](https://github.com/NullixAT/buhax)
* [Our own unit tests running a full instance of an application built with framelix](https://github.com/NullixAT/framelix-tests)

## Setup

You will find detailed setup instructions for production setup in the corresponding app
repositories.

### Development

Changes to this docker repository requires to install our test repository. You can
find [the instructions for that here](https://github.com/NullixAT/framelix-tests).
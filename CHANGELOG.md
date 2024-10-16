# [3.8.0](https://github.com/nextcloud/mail/compare/v3.7.8...v3.8.0) (2024-10-16)


### Bug Fixes

* **deps:** Apply npm audit fix ([cb38954](https://github.com/nextcloud/mail/commit/cb38954db6833a52321d0bddff48d99c85d89f4e))
* **deps:** Apply npm audit fix ([d8fc40a](https://github.com/nextcloud/mail/commit/d8fc40a5041c747bd2f966784e849457319920bc))
* fetch attendance status when calendars are loaded ([b1d6c90](https://github.com/nextcloud/mail/commit/b1d6c90dc5ab5b5b664cb98896745ab83d64c6b9)), closes [/github.com/nextcloud/mail/blob/6fc45eb0630b9065f9ccb4c1da5cc9557f7df834/src/App.vue#L49-L50](https://github.com//github.com/nextcloud/mail/blob/6fc45eb0630b9065f9ccb4c1da5cc9557f7df834/src/App.vue/issues/L49-L50)
* fix renaming mailbox hierarchy ([11d582b](https://github.com/nextcloud/mail/commit/11d582b294d51d71c389a2ecdac2cefa145ca894))
* **iframe:** scroll horizontally in case of overflow ([72d3775](https://github.com/nextcloud/mail/commit/72d3775c47866a2fdd676a20d5d1c8c5f6f46567))
* **imap:** do a single full sync when QRESYNC is enabled ([cdf550c](https://github.com/nextcloud/mail/commit/cdf550c81d4e31909a75ec1e6b9d4b3ad720d8d0))
* **imap:** persist vanished messages immediately on EXAMINE commands ([a2c9e8c](https://github.com/nextcloud/mail/commit/a2c9e8cdf6abfe3648e17decaa82407878ffd178))
* select multiple envelopes by holding shift directly ([5aec041](https://github.com/nextcloud/mail/commit/5aec0416f37bd1a7f7e906bb41cc707acd2c6d15))


### Features

* implement periodic full sync job to repair cache inconsistencies ([e267da1](https://github.com/nextcloud/mail/commit/e267da1633e8205fca69721e9d9dad4bbe6bfc6e))


### Performance Improvements

* don't loop the users without any provisioning configurations ([3b1f46b](https://github.com/nextcloud/mail/commit/3b1f46b269d816256ea5d50c6f6e0a7c906e283e))
* skip non-writable calendars ([d5f6a1e](https://github.com/nextcloud/mail/commit/d5f6a1ee5b130da581db206aa38e784d4ff36c0b))



## [3.7.8](https://github.com/nextcloud/mail/compare/v3.7.7...v3.7.8) (2024-08-28)


### Bug Fixes

* **deps:** Apply npm audit fix ([f8dd52b](https://github.com/nextcloud/mail/commit/f8dd52b03e7462d77727db9f223dc90ae96fd71f))
* **deps:** Apply npm audit fix ([1b44820](https://github.com/nextcloud/mail/commit/1b44820b523e4fb6eab564da6646046e1ee915fe))
* **deps:** Apply npm audit fix ([1a37e7f](https://github.com/nextcloud/mail/commit/1a37e7fe69248fd648e13a503eba919a9d92f328))
* don't fail on missing mailbox stats ([792d5c9](https://github.com/nextcloud/mail/commit/792d5c938787759063eaebf079f25fac31ca7158))
* **integration:** Honor sharing to group members restriction ([8c60504](https://github.com/nextcloud/mail/commit/8c60504adee0445685e2c1b04137705216cf4560))
* send imip when importing an event in mail ([f9a07a8](https://github.com/nextcloud/mail/commit/f9a07a80cabab03570431c43cc9ee667ba649fcb)), closes [/github.com/nextcloud/3rdparty/blob/ea2fabbd358c9e0f9dae43bcb242b0cf8ee0d178/sabre/vobject/lib/ITip/Broker.php#L245-L254](https://github.com//github.com/nextcloud/3rdparty/blob/ea2fabbd358c9e0f9dae43bcb242b0cf8ee0d178/sabre/vobject/lib/ITip/Broker.php/issues/L245-L254)
* Show text in empty mailbox view ([176a9cf](https://github.com/nextcloud/mail/commit/176a9cfa4a5a09c0906e0e49a96aa4c41c0910a7))



## [3.7.7](https://github.com/nextcloud/mail/compare/v3.7.6...v3.7.7) (2024-08-09)


### Bug Fixes

* **autoconfig:** Refactor DNS query for testing ([199c428](https://github.com/nextcloud/mail/commit/199c428546ecd94682513586d6f1764714fec750))
* **deps:** Apply npm audit fix ([6d2075c](https://github.com/nextcloud/mail/commit/6d2075c78d8a88d629f5fbd6cbee2659b0a96211))



## [3.7.6](https://github.com/nextcloud/mail/compare/v3.7.5...v3.7.6) (2024-07-30)


### Bug Fixes

* **deps:** Apply npm audit fix ([e891f64](https://github.com/nextcloud/mail/commit/e891f64ba33880bc172cc71e34c1fc6a9262dd7e))
* lost focus in reference picker ([09c4110](https://github.com/nextcloud/mail/commit/09c4110d5578236e912b0f703c884b5ebf783c10))
* set link icon size explicitly ([8ded095](https://github.com/nextcloud/mail/commit/8ded0954cd4dae7c41af0fb7d8a636305b299035))



## [3.7.5](https://github.com/nextcloud/mail/compare/v3.7.4...v3.7.5) (2024-07-17)


### Bug Fixes

* **deps:** Apply npm audit fix ([3ffe2d3](https://github.com/nextcloud/mail/commit/3ffe2d3f94d820b65b0919f35198d3c2a8784712))
* move delete duplicate uids repair step to a job ([04d131a](https://github.com/nextcloud/mail/commit/04d131a77b9c4422f312f17d7fb21a8c36dff779))
* remove the global styling from the composer list ([b0d9a55](https://github.com/nextcloud/mail/commit/b0d9a5589f2c7db3f11c93986c8abb726e2b81fb))



## [3.7.4](https://github.com/nextcloud/mail/compare/v3.7.3...v3.7.4) (2024-07-15)


### Bug Fixes

* add repair job to deleted duplicated cached messages ([7075e64](https://github.com/nextcloud/mail/commit/7075e640b4651a3b1a736103359ed80a4f4bfbb7))
* **db:** Delete recipients without sub query ([50328e1](https://github.com/nextcloud/mail/commit/50328e1876ec81c69a4692e4bf7e8a1d485240b3))
* **deps:** Apply npm audit fix ([64fba8c](https://github.com/nextcloud/mail/commit/64fba8cfb8dbe9f684f901757e0f49f8a1998f5e))
* duplicate uid repair job failing on postgres ([48f1b33](https://github.com/nextcloud/mail/commit/48f1b33620f5d7c42d3cba8033d8947dcf4b7f9c))
* **printing:** Fix long emails getting cut for print ([e73f285](https://github.com/nextcloud/mail/commit/e73f28595049d0eb660828579ea128ec546116ae))



## [3.7.3](https://github.com/nextcloud/mail/compare/v3.7.2...v3.7.3) (2024-07-04)


### Bug Fixes

* align reply and attachment icon with subject ([46d2d6d](https://github.com/nextcloud/mail/commit/46d2d6d9a27eaedf33c5bc8bed24cf313fa34208))
* mailbox error empty content alignment ([b1edcc9](https://github.com/nextcloud/mail/commit/b1edcc944bc8cfc55f79270011e051d53f4f4a40))



## [3.7.2](https://github.com/nextcloud/mail/compare/v3.7.1...v3.7.2) (2024-06-25)


### Bug Fixes

* **.nextcloudignore:** Exclude php-stemmer tests from package ([1a57dab](https://github.com/nextcloud/mail/commit/1a57dab0e8752457b8b8c1991137e8df6c9af6b2)), closes [#9586](https://github.com/nextcloud/mail/issues/9586)
* **deps:** Apply npm audit fix ([f18d496](https://github.com/nextcloud/mail/commit/f18d49690b81421b49a5f180ad01e2ab9dce1087))
* honour MDN requests ([99cf34f](https://github.com/nextcloud/mail/commit/99cf34f45d1b67b35ac9f287179081f5c4b438f5))
* **outbox:** handle missing raw message gracefully ([2b1d678](https://github.com/nextcloud/mail/commit/2b1d678f3eac8b8240b656823dfa970e8b65f291))
* save horde cache backend on imap client logout ([34da7d2](https://github.com/nextcloud/mail/commit/34da7d2fdafd2ab0ad3c5abd3ed3cadecd45fedb))


### Features

* add backend check for download permission for cloud attachements ([e23dcc4](https://github.com/nextcloud/mail/commit/e23dcc4ea06f1b8886f752886055f72aeb2621b1))



## [3.7.1](https://github.com/nextcloud/mail/compare/v3.7.0...v3.7.1) (2024-06-06)



# [3.7.0](https://github.com/nextcloud/mail/compare/v3.7.0-beta2...v3.7.0) (2024-06-04)


### Bug Fixes

* inconsistent encoding in saved sent messages ([731669f](https://github.com/nextcloud/mail/commit/731669f79be37fae58195d21066de765e1e23939))
* **smime:** use whole certificate chain ([95cc454](https://github.com/nextcloud/mail/commit/95cc45456ecbeafded6d37d1d7f138f9fe1e52a0)), closes [#9190](https://github.com/nextcloud/mail/issues/9190)
* using shortcut to select drafts shouldnt open the composer ([c298e0d](https://github.com/nextcloud/mail/commit/c298e0dcee15863e62c2b0bb20e9da404376135c))



# [3.7.0-beta2](https://github.com/nextcloud/mail/compare/v3.7.0-beta1...v3.7.0-beta2) (2024-05-28)


### Bug Fixes

* **composer:** Revive ckeditor translations ([a844d73](https://github.com/nextcloud/mail/commit/a844d73ba5aae7cd101d029d343388b815864eb0))
* **deps:** Apply npm audit fix ([3a45f12](https://github.com/nextcloud/mail/commit/3a45f12d485de189ae4de7dbcdeb37d78259b800))



# [3.7.0-beta1](https://github.com/nextcloud/mail/compare/v3.6.0-beta3...v3.7.0-beta1) (2024-05-06)


### Bug Fixes

* **composer:** Prevent leaving the tab with unsaved changes ([e57db7b](https://github.com/nextcloud/mail/commit/e57db7b01bd7b2bfd793ccb58fd464300cf8fd9d))
* **deps:** bump @ckeditor/ckeditor5-editor-decoupled from 37.0.1 to v37.1.0 (main) ([#9480](https://github.com/nextcloud/mail/issues/9480)) ([1a63205](https://github.com/nextcloud/mail/commit/1a632052ff6c545c43274a6a7b4088b80b8b4614))
* **deps:** bump @nextcloud/dialogs from 5.2.0 to ^5.3.0 (main) ([#9572](https://github.com/nextcloud/mail/issues/9572)) ([9da3f56](https://github.com/nextcloud/mail/commit/9da3f5612965f343fcffa60fc592340bad572cde))
* **deps:** bump @nextcloud/dialogs from 5.3.0 to ^5.3.1 (main) ([#9595](https://github.com/nextcloud/mail/issues/9595)) ([e2ce479](https://github.com/nextcloud/mail/commit/e2ce479480d71a2d6abfadcaa14007098c7e5554))
* **deps:** bump @nextcloud/files from 3.1.0 to ^3.1.1 (main) ([#9499](https://github.com/nextcloud/mail/issues/9499)) ([97fbbe9](https://github.com/nextcloud/mail/commit/97fbbe9c40235e618eea9e9068fd9ec2df19c4d6))
* **deps:** bump @nextcloud/files from 3.2.1 to ^3.2.1 (main) ([#9597](https://github.com/nextcloud/mail/issues/9597)) ([44af721](https://github.com/nextcloud/mail/commit/44af72138b06c017d285e25e4781f5e7ed0d5286))
* **deps:** bump @nextcloud/router from 3.0.0 to ^3.0.1 (main) ([#9596](https://github.com/nextcloud/mail/issues/9596)) ([cf9dd13](https://github.com/nextcloud/mail/commit/cf9dd13507befd1e45185e7aae1a0af5e3237655))
* **deps:** bump @nextcloud/vue from 8.11.0 to ^8.11.1 ([e91bea8](https://github.com/nextcloud/mail/commit/e91bea82bc4c77f9b2aa56286d8209c1a87c2ec6))
* **deps:** bump @nextcloud/vue from 8.11.1 to ^8.11.2 ([22a5ae4](https://github.com/nextcloud/mail/commit/22a5ae40b71057c1611a7773f34842d9ce38145c))
* **deps:** bump address-rfc2822 from 2.2.0 to ^2.2.1 (main) ([#9545](https://github.com/nextcloud/mail/issues/9545)) ([80e82ff](https://github.com/nextcloud/mail/commit/80e82ff3c3a6d920244e4d639e164da703d7e178))
* **deps:** bump address-rfc2822 from 2.2.1 to ^2.2.2 (main) ([#9618](https://github.com/nextcloud/mail/issues/9618)) ([5be2d9f](https://github.com/nextcloud/mail/commit/5be2d9fd204280cdd77a56dd471ea633c7539029))
* **deps:** bump dompurify from 3.0.10 to ^3.0.11 ([94b5093](https://github.com/nextcloud/mail/commit/94b50936147d27097154e872bb9debda5a965ac7))
* **deps:** bump dompurify from 3.0.11 to ^3.1.0 (main) ([#9546](https://github.com/nextcloud/mail/issues/9546)) ([1be5cce](https://github.com/nextcloud/mail/commit/1be5ccec727847914944ab1625b2f101c47cf046))
* **deps:** bump iframe-resizer from 4.3.9 to ^4.3.11 (main) ([#9598](https://github.com/nextcloud/mail/issues/9598)) ([4b08392](https://github.com/nextcloud/mail/commit/4b0839274d0f604dd9a9e877bb15184697142862))
* **deps:** bump nextcloud/kitinerary-bin from 1.0.2 to ^1.0.3 ([774ac9f](https://github.com/nextcloud/mail/commit/774ac9f5302a51d3526cc3811aa1fffc7448bcef))
* **deps:** bump stylelint from 16.2.1 to ^16.3.1 ([06c5976](https://github.com/nextcloud/mail/commit/06c59764a33c847a6a1d03ed0a203d18e24a4ffd))
* **deps:** bump webdav from 5.4.0 to ^5.5.0 ([8316cc7](https://github.com/nextcloud/mail/commit/8316cc729955e7c254a8ac703f36add04214e6e7))
* **deps:** Replace @nextcloud/vue-dashboard with @nextcloud/vue ([c1cc553](https://github.com/nextcloud/mail/commit/c1cc553ac9e8ce46d8ed643abfcea3e0430782f8))
* **files:** add static icon for unknown user ([bdb5d30](https://github.com/nextcloud/mail/commit/bdb5d30bf8abf5ebef099459b21929e0da609d24))
* **jobs:** Skip background jobs if no authentication is possible ([f1d3fda](https://github.com/nextcloud/mail/commit/f1d3fda3015c1ace393124a25cf588e37c879981))
* **outbox:** add status for messages ([7c59040](https://github.com/nextcloud/mail/commit/7c590407858fa4076e76407eeb545234bd3782ce))
* **outbox:** handle indeterminate smtp errors ([a1daf35](https://github.com/nextcloud/mail/commit/a1daf351f83e27ea1fc4bac2d112966560f04501))
* PHP deprecations ([d62ac04](https://github.com/nextcloud/mail/commit/d62ac040c4a64501425e068553f54b3331f5d412))
* **pi:** section title margins affected by global styles ([30abd01](https://github.com/nextcloud/mail/commit/30abd01dae8c2ab5bf53be814b4c1bde2a4a9303))
* remove deprecated prototype.substr() method ([2050173](https://github.com/nextcloud/mail/commit/20501734b0ba0e45dd095df61ea7a7a998d56317))
* remove ununsed package ([01c4faf](https://github.com/nextcloud/mail/commit/01c4fafb933f2538cfda8a688eebb8d57695359b))
* Sanitize forward slashes from name before generating url ([b29bfda](https://github.com/nextcloud/mail/commit/b29bfdadfac3be3bd5a9f8f2bfe2063c3a5f4719))
* **threading:** Run manual garbage collection ([060923f](https://github.com/nextcloud/mail/commit/060923f2cb9634914f413e9f56c235b3715d9e83))


### Features

* **deps:** Drop Nextcloud 26, add 30 support ([7101ad2](https://github.com/nextcloud/mail/commit/7101ad2e4feafc1093d24fc2e3a7f2c16091a361))
* implement admin setting to disable classification by default ([96c1259](https://github.com/nextcloud/mail/commit/96c12592e888327f939308266db2a7bf6ba7e74a))
* **utility:** make json methods work the same for ([bfa0d7b](https://github.com/nextcloud/mail/commit/bfa0d7b3013212e21782e27518c8d7c79749a24c))


### Reverts

* Revert "Fix: generate event data fails for threads across multiple mailboxes" ([09ded27](https://github.com/nextcloud/mail/commit/09ded27c2215a2516693702962c29a1b2cc2ae10))



# [3.6.0-beta3](https://github.com/nextcloud/mail/compare/v3.6.0-beta2...v3.6.0-beta3) (2024-03-20)


### Bug Fixes

* **deps:** Apply npm audit fix ([c5e76e6](https://github.com/nextcloud/mail/commit/c5e76e68d0f7efdbac2b922174e81507039ea67c))
* **deps:** bump @nextcloud/cdav-library from 1.1.0 to ^1.3.0 (main) ([#9437](https://github.com/nextcloud/mail/issues/9437)) ([f26f421](https://github.com/nextcloud/mail/commit/f26f4218052238410e3e8441fe00133e12cf4cc3))
* **deps:** bump @nextcloud/dialogs from 5.1.2 to ^5.2.0 (main) ([#9481](https://github.com/nextcloud/mail/issues/9481)) ([78308d9](https://github.com/nextcloud/mail/commit/78308d92ea25cad30dbd3fa134b8fd3127c62d18))
* **deps:** bump @nextcloud/vue from 8.10.0 to ^8.11.0 ([481e00c](https://github.com/nextcloud/mail/commit/481e00cd8d2abf8e31b6219e5cc077baceb8d79a))
* **deps:** bump bytestream/horde-imap-client from 2.33.1 to ^2.33.2 (main) ([#9460](https://github.com/nextcloud/mail/issues/9460)) ([6f4e2c5](https://github.com/nextcloud/mail/commit/6f4e2c52f3e070c09dd01fcbb3369731e42be54b))
* **deps:** bump core-js from 3.36.0 to ^3.36.1 (main) ([#9475](https://github.com/nextcloud/mail/issues/9475)) ([6d1442d](https://github.com/nextcloud/mail/commit/6d1442d2e4b383000f27f48152c8009cd5bf88ee))
* **deps:** bump dompurify from 3.0.9 to ^3.0.10 (main) ([#9476](https://github.com/nextcloud/mail/issues/9476)) ([aca2138](https://github.com/nextcloud/mail/commit/aca21388ebc5eb630a7ecb6e40306e6ddd546889))
* display iCloud messages ([8417e52](https://github.com/nextcloud/mail/commit/8417e52e517bdad8003c9d63a2a66f0268bb4c9b))



# [3.6.0-beta2](https://github.com/nextcloud/mail/compare/v3.6.0-beta1...v3.6.0-beta2) (2024-03-12)


### Bug Fixes

* add command to repair broken autoresponders ([60b199e](https://github.com/nextcloud/mail/commit/60b199ebcbd818652bafcfea0958d7b32b4ea5c6))
* add name_hash as nullable ([d8de6d2](https://github.com/nextcloud/mail/commit/d8de6d2e30332a3a51d37225de680574c137f0ab))
* Alien envelopes ([8dfae53](https://github.com/nextcloud/mail/commit/8dfae5396194f6b6613f9e6dafc946e682f03fe0))
* allow syncing of mailboxes with a trailing space ([9783069](https://github.com/nextcloud/mail/commit/9783069f6b8bb36f57d0b3e65a8384cebadf93f2))
* **autoresponder:** use subject placeholder instead of ooo short message ([5899ebe](https://github.com/nextcloud/mail/commit/5899ebe00a2911421160396c49a7e74c0d5fa780))
* cache accounts by userId in AccountService ([c69c7d7](https://github.com/nextcloud/mail/commit/c69c7d724feb7ac943ff61c7ab57f93e2f1dde96))
* **clean-up:** remove all outdated classifier not only 100 per day ([0e3fcce](https://github.com/nextcloud/mail/commit/0e3fccec8f9555cd8d1b2bf72092120d73005ba3))
* **composer:** creating new options in recipient selects ([ffd78d5](https://github.com/nextcloud/mail/commit/ffd78d51c3bb46035ee705cb8a52de1699da98ba))
* **composer:** duplicate label in to, cc and bcc selects ([5fec0d1](https://github.com/nextcloud/mail/commit/5fec0d1f19467dd2826e24c43e66cb114911a30b))
* **db:** add version check for index with length ([2cafaa1](https://github.com/nextcloud/mail/commit/2cafaa1ae08185d46c81feef451a447a26c821ad))
* **db:** Avoid dirty read for collected addresses ([66fdc0b](https://github.com/nextcloud/mail/commit/66fdc0bbe0b6497320eef449cc5ed644e91025fd))
* **db:** Avoid dirty read for local message updates ([fff840a](https://github.com/nextcloud/mail/commit/fff840ac4f88c12cd484af0cd730a913e92e6865))
* **db:** Avoid dirty reads while deleting messages by uid ([e92114e](https://github.com/nextcloud/mail/commit/e92114e90e7733d1a7eccfa94067fb5699df7075))
* **deps:** Apply npm audit fix ([e97f337](https://github.com/nextcloud/mail/commit/e97f337049be90e03f28dddec57bcf5af0555410))
* **deps:** bump @nextcloud/calendar-js from 6.0.1 to ^6.1.0 (main) ([#9068](https://github.com/nextcloud/mail/issues/9068)) ([00bf78b](https://github.com/nextcloud/mail/commit/00bf78bfed62af45678eeb6a08c130378a6d48f2))
* **deps:** bump @nextcloud/dialogs from 4.1.0 to ^4.2.5 (main) ([#8797](https://github.com/nextcloud/mail/issues/8797)) ([cadad6e](https://github.com/nextcloud/mail/commit/cadad6e2245c6c5a6f47308a1cdf22eda6c3d4e7))
* **deps:** bump @nextcloud/dialogs from 4.2.5 to ^4.2.6 (main) ([#9377](https://github.com/nextcloud/mail/issues/9377)) ([210d79c](https://github.com/nextcloud/mail/commit/210d79cc9d88e185298409b00c48fb0681f2d297))
* **deps:** bump @nextcloud/dialogs from 4.2.6 to v5 ([4679883](https://github.com/nextcloud/mail/commit/4679883e2eabc871bd4fcd171a18a47262635b9a))
* **deps:** bump @nextcloud/files from 2.1.0 to v3 ([2de7e60](https://github.com/nextcloud/mail/commit/2de7e60c13ca3eda51aa7159779e706b22690e6a))
* **deps:** bump @nextcloud/moment from 1.2.2 to ^1.3.1 (main) ([#9396](https://github.com/nextcloud/mail/issues/9396)) ([1932e0f](https://github.com/nextcloud/mail/commit/1932e0f427993f2554597f4050fcdf18a3a77beb))
* **deps:** bump @nextcloud/router from 2.2.0 to ^2.2.1 (main) ([#9360](https://github.com/nextcloud/mail/issues/9360)) ([9dd2149](https://github.com/nextcloud/mail/commit/9dd2149922af23b4b3fc8506e7a695fc37d252bd))
* **deps:** bump @nextcloud/router from 2.2.1 to v3 ([b11cd98](https://github.com/nextcloud/mail/commit/b11cd9849916668db903cad34b0180cbe70fee44))
* **deps:** bump @nextcloud/vue from 7.12.6 to ^7.12.7 ([6af3637](https://github.com/nextcloud/mail/commit/6af36374027ad41a7f1fd9b5f8737c035315b019))
* **deps:** bump @nextcloud/vue from 8.5.0 to ^8.6.2 ([f68dd5a](https://github.com/nextcloud/mail/commit/f68dd5aead98d77dcf7914fa8dc2ba6e03406301))
* **deps:** bump @nextcloud/vue from 8.6.2 to ^8.10.0 ([33a3e38](https://github.com/nextcloud/mail/commit/33a3e383a6494c16674f6907d4cfcbe8e22a99dd))
* **deps:** bump address-rfc2822 from 2.1.0 to ^2.2.0 (main) ([#9405](https://github.com/nextcloud/mail/issues/9405)) ([584ed59](https://github.com/nextcloud/mail/commit/584ed59235b1c248decbca3238343498ed37a152))
* **deps:** bump core-js from 3.33.2 to ^3.33.3 (main) ([#9083](https://github.com/nextcloud/mail/issues/9083)) ([1393394](https://github.com/nextcloud/mail/commit/13933946ba76d1e6ec8d53b191bc7f88e401a98f))
* **deps:** bump core-js from 3.33.3 to ^3.34.0 (main) ([#9166](https://github.com/nextcloud/mail/issues/9166)) ([4ee62af](https://github.com/nextcloud/mail/commit/4ee62afe03553201a5ecef5036974b96af92e8c2))
* **deps:** bump core-js from 3.34.0 to ^3.36.0 (main) ([#9406](https://github.com/nextcloud/mail/issues/9406)) ([4afd9d8](https://github.com/nextcloud/mail/commit/4afd9d85fda87194dd7b26e2c93dfe76623d58e2))
* **deps:** bump dompurify from 3.0.6 to ^3.0.9 (main) ([#9270](https://github.com/nextcloud/mail/issues/9270)) ([f72b9f4](https://github.com/nextcloud/mail/commit/f72b9f427179e032096481df7e0a9c6a29ac6c03))
* **deps:** bump ezyang/htmlpurifier from 4.16.0 to v4.17.0 (main) ([#9085](https://github.com/nextcloud/mail/issues/9085)) ([a1e4110](https://github.com/nextcloud/mail/commit/a1e41106258eadeb1d98d5bb58e815c04f49aa00))
* **deps:** bump iframe-resizer from 4.3.7 to ^4.3.9 (main) ([#9084](https://github.com/nextcloud/mail/issues/9084)) ([68c9672](https://github.com/nextcloud/mail/commit/68c96723f491461c3e06325d43a3cf908c5d2d1f))
* **deps:** bump js-base64 from 3.7.5 to ^3.7.6 (main) ([#9361](https://github.com/nextcloud/mail/issues/9361)) ([6eadbce](https://github.com/nextcloud/mail/commit/6eadbcefd7dcd8f230f23fb26a6c26e03c41ad50))
* **deps:** bump js-base64 from 3.7.6 to ^3.7.7 (main) ([#9378](https://github.com/nextcloud/mail/issues/9378)) ([cbe62e4](https://github.com/nextcloud/mail/commit/cbe62e4890887650fde156d254d6891f482d24c5))
* **deps:** bump nextcloud/kitinerary-bin from 1.0.0 to ^1.0.2 (main) ([#9379](https://github.com/nextcloud/mail/issues/9379)) ([2132361](https://github.com/nextcloud/mail/commit/2132361862dc2c4774993544afcfcfbe3820228e))
* **deps:** bump p-limit from 4.0.0 to v5 ([2b99aba](https://github.com/nextcloud/mail/commit/2b99aba140a505c63f787298e0a410a45b7beda4))
* **deps:** bump sabberworm/php-css-parser from 8.4.0 to ^8.5.1 (main) ([#9409](https://github.com/nextcloud/mail/issues/9409)) ([0a52127](https://github.com/nextcloud/mail/commit/0a521273f4916b02479b44284ff06f95535484f1))
* **deps:** bump stylelint from 15.11.0 to v16 ([5238442](https://github.com/nextcloud/mail/commit/5238442d0cd3f34c6e11e2ece208b2519b25a47d))
* **deps:** bump vue monorepo from 2.7.15 to ^2.7.16 (main) ([#9380](https://github.com/nextcloud/mail/issues/9380)) ([f2015c4](https://github.com/nextcloud/mail/commit/f2015c4a9f433d470b9b33b52145ef81581bb3b5))
* **deps:** bump vue-material-design-icons from 5.2.0 to ^5.3.0 (main) ([#9407](https://github.com/nextcloud/mail/issues/9407)) ([3c2db50](https://github.com/nextcloud/mail/commit/3c2db50eb68f3e629f67ea4f6d9d9fb50dcf13ca))
* **deps:** bump webdav from 4.11.3 to v5 ([184fba5](https://github.com/nextcloud/mail/commit/184fba5ad2a1960aac7905b905c17ab7c0878559))
* **deps:** Switch KItinerary vendor ([736731f](https://github.com/nextcloud/mail/commit/736731f1b0169178a8e2fc79a2c8fa073cc42e8c))
* **encoding:** better character encoding ([176e074](https://github.com/nextcloud/mail/commit/176e074df28a0a6d0c335ff141648743d3cb96c7))
* Hack CSS to restore envelope styling ([d8c9f4e](https://github.com/nextcloud/mail/commit/d8c9f4edf898d11fb81e59273697a7cc3e663da6))
* **imap:** Only rate limit actual auth errors ([b19918d](https://github.com/nextcloud/mail/commit/b19918dbd5d1f63aef32d6c738bc9a45117e2832))
* **imap:** Pass flags as array for the STORE command ([4d1879d](https://github.com/nextcloud/mail/commit/4d1879d3e25777c69108ba8093d6c2888173e0da))
* improve autoresponder time zone handling ([f0847be](https://github.com/nextcloud/mail/commit/f0847be20afb86850f57d40cd03ab0f052219846))
* **integration:** Allow LLM event titles/agendas for non-admins ([2823ea2](https://github.com/nextcloud/mail/commit/2823ea27bf876b3e66750133f3ba8681fc2d00a6))
* listen to more out-of-office events to prevent missed changes ([ee63978](https://github.com/nextcloud/mail/commit/ee639783bab0576eb12814f0c69d418ff83ea49e))
* **message:** Translate task description label ([8887dbb](https://github.com/nextcloud/mail/commit/8887dbb3d1ddf5a320c4416daad2527d14d546d3))
* **navigation:** Only make mailboxes with children collapsible ([ae43682](https://github.com/nextcloud/mail/commit/ae436822833038ec497db04836f6a63ce34310a0))
* only load thread summary for threads with at least 3 messages. ([88ad956](https://github.com/nextcloud/mail/commit/88ad956d2e8dd059bde23daea9d076c695890b08))
* **provisioning:** Clean up orphaned accounts ([ed228bb](https://github.com/nextcloud/mail/commit/ed228bb807db59a17b2250c92a20af7083c028fb))
* **provisioning:** Do not require master password if disabled ([410e9bb](https://github.com/nextcloud/mail/commit/410e9bba356aa9d3508169be256ffe5d4f6c7244))
* **quota:** don't divide by limit zero ([dd788e7](https://github.com/nextcloud/mail/commit/dd788e74df60acc4ca92afb0573a1de1e7d3797a))
* **search:** Align advanced search UI elements better ([7b42142](https://github.com/nextcloud/mail/commit/7b4214215cd10b3ffea5a1eddfb2ba6435dd2646))
* **search:** Reduce advanced search icon size ([6042454](https://github.com/nextcloud/mail/commit/60424542fbd8d2cf466506fbaaf28de0f6964d27))
* **setup:** Fix rate limit annotation syntax ([#9216](https://github.com/nextcloud/mail/issues/9216)) ([a3f1970](https://github.com/nextcloud/mail/commit/a3f197033a8f3ed3636177a4af9310564157d3c2)), closes [nextcloud/mail#9170](https://github.com/nextcloud/mail/issues/9170) [/github.com/nextcloud/server/blob/7502c19ddd43853c3b4fad1e2df91aed19e6b626/lib/private/AppFramework/Utility/ControllerMethodReflector.php#L66](https://github.com//github.com/nextcloud/server/blob/7502c19ddd43853c3b4fad1e2df91aed19e6b626/lib/private/AppFramework/Utility/ControllerMethodReflector.php/issues/L66)
* **setup:** Increase rate limit ([89876e1](https://github.com/nextcloud/mail/commit/89876e13b3e94082582c8fd466261c5c12319e43))
* **setup:** Sort MX records by weight ([684a215](https://github.com/nextcloud/mail/commit/684a215e8119d4f30b826c1da63a1f83972fb3cd))
* **setup:** Use MX record TLD for ISPDB lookup ([1b2f3a0](https://github.com/nextcloud/mail/commit/1b2f3a012fb8be2ac2ec6dc5fb4294bff9a4e618))
* **setup:** Use MX sort result ([da9b578](https://github.com/nextcloud/mail/commit/da9b57815fe223468fbe60630346c38da920f934))
* **smime:** alias to cert mapping in account settings ([ed7c152](https://github.com/nextcloud/mail/commit/ed7c15297f00c24e122d87c0eea029d23d670bbc))
* **sync:** force full sync when the server reports QRESYNC ([eacd84c](https://github.com/nextcloud/mail/commit/eacd84c7b279e3b0659611f8e0fe9628ed3a207a))
* Use property_exists for SimpleXMLElement checks ([3e8aeec](https://github.com/nextcloud/mail/commit/3e8aeec36a141961f31ae69d46a9e3cb123ce024))


### Features

* apply personal out-of-office data to the auto responder ([3cbdf1f](https://github.com/nextcloud/mail/commit/3cbdf1f302aa136db72feda1e32bbdb76f3622ee))
* **composer:** Use editor toolbar instead of balloon ([e9f068f](https://github.com/nextcloud/mail/commit/e9f068fd0c225f48cbc031b929743edb4155e31a))
* **deps:** Support Nextcloud 29 ([5b43ae2](https://github.com/nextcloud/mail/commit/5b43ae2a19ff88f463369e8e232d76cefd9fb86c))
* **integration:** Combine all LLM feature flags ([8c934bd](https://github.com/nextcloud/mail/commit/8c934bd74785abc8b43fb2e5f33fcb8ba3f8ae86))
* **integration:** Support DESCRPTION for events ([f80bef4](https://github.com/nextcloud/mail/commit/f80bef4dbb0426903dabe019b75f6ef6801b115b))
* **integration:** Use LLM to fill event details ([eab26c4](https://github.com/nextcloud/mail/commit/eab26c477374cb2179f433e36d4ff0ace0ba9582))
* move threads not messages in drag-and-drop ([c18a1e4](https://github.com/nextcloud/mail/commit/c18a1e46b389130d53140380c46f85b4216c9a76))
* **smime:** allow selection of untrusted certs ([6111216](https://github.com/nextcloud/mail/commit/611121620e0d489216d2aa2de569ba54a397079a))


### Performance Improvements

* Add performance logger to clean up ([48550d6](https://github.com/nextcloud/mail/commit/48550d64dae9d7483fec21941cb4df7f4ae12e65))
* **db:** Add message_id index for mail_messages table ([7e32031](https://github.com/nextcloud/mail/commit/7e32031d563a0437fbbe382807e9e8b1ca74f2e2))
* **db:** optimise indices of mail tables ([4c356d6](https://github.com/nextcloud/mail/commit/4c356d6995d1ce6a38763f855e309dfb02e37eeb))
* **sync:** Reduce db operations for accounts without CONDSTORE ([413dfbb](https://github.com/nextcloud/mail/commit/413dfbb6b0e772ebe0b44f75f6a6057582165b8f))



# [3.5.0-beta3](https://github.com/nextcloud/mail/compare/v3.5.0-beta2...v3.5.0-beta3) (2023-11-14)


### Features

* **advanced-search:** allow date and recipient search ([8e12c63](https://github.com/nextcloud/mail/commit/8e12c633ddd3c20c70099c9ab94705d9eb44f7f0))



# [3.5.0-beta2](https://github.com/nextcloud/mail/compare/v3.5.0-beta1...v3.5.0-beta2) (2023-11-09)


### Bug Fixes

* **accessibility:** Add arial-label to NcActionButtons without text ([7c440e1](https://github.com/nextcloud/mail/commit/7c440e130e6cb3ff4ca3cd76f01d0262a18c1d03))
* add expiration for itinerary cache ([4c60847](https://github.com/nextcloud/mail/commit/4c608472c08c465713b9e14f32877dd23ce0e4e2))
* Add missing background box for redirect page ([953998e](https://github.com/nextcloud/mail/commit/953998e0af98453f5f2cf3edfe7f10bc489c431c))
* add preview enhancement job to new accounts joblist ([09ba345](https://github.com/nextcloud/mail/commit/09ba345edf21d6a13ca994b672af215dd37e3052))
* Allow dynamic autoloading for classes added during upgrade ([d81bcca](https://github.com/nextcloud/mail/commit/d81bcca8c147019b506f6011ce9c8863f18814b3))
* allow sending of messages with empty body content ([646ccfe](https://github.com/nextcloud/mail/commit/646ccfe38e54877d06de934ee61774a4688d1dfb))
* archiving messages via shortcut ([0569444](https://github.com/nextcloud/mail/commit/0569444b2908b17198ba4419679ee0322dd639ac))
* **attachments:** fetch message once when saving attachments in files ([f7921f1](https://github.com/nextcloud/mail/commit/f7921f19106b0e0584fccb5260b0c0a544706b4d))
* **autocomplete:** make system address book searchable not just full matches ([d8be257](https://github.com/nextcloud/mail/commit/d8be2578ca29e272e334c7ec69467c9c5ea25edf))
* **avatar:** Validate favicon hosts ([4d63219](https://github.com/nextcloud/mail/commit/4d6321962973c947b21181cc9ffb7e272d9cb926))
* **bgJob:** Fix DB-Query for open drafts ([4530a34](https://github.com/nextcloud/mail/commit/4530a34ec5e69a4d94e3af62636f430760f29234))
* button style ([6bb69c7](https://github.com/nextcloud/mail/commit/6bb69c7f0c5e61b40a31ce5a3bb1e3a3d8ef0932))
* Check strict cookies for image proxy ([be36e3e](https://github.com/nextcloud/mail/commit/be36e3e342c0d889670ac80434f07c5cabba6159))
* **classification:** Delete historic data ([8e1e5ea](https://github.com/nextcloud/mail/commit/8e1e5ea65f6db951a95cdec50f50d3f29880a554))
* **classification:** refactor persistence ([a23dac9](https://github.com/nextcloud/mail/commit/a23dac94a52d98cd7c64d62944b7d217bb8a0170))
* **cleanup:** clean up mail_message_tags and mail_tags ([68e0b9b](https://github.com/nextcloud/mail/commit/68e0b9b0c1efd1efe9b73daabd3d81a9ccf6dfc4))
* **composer:** add gap between primary actions ([4a90cd8](https://github.com/nextcloud/mail/commit/4a90cd809c0e2e7f6e9ff652f1f885e3568cbb41))
* **composer:** forward messages as attachments ([81fbc14](https://github.com/nextcloud/mail/commit/81fbc143aea28dbdf220c9e57390b4de11583eba))
* Convert drafts to outbox messages before sending ([b74afd3](https://github.com/nextcloud/mail/commit/b74afd3fee114ff9b0e1460c12e95a20d2c71a09))
* dashboard loading forever ([bac8f1a](https://github.com/nextcloud/mail/commit/bac8f1ae8ba32f2c3b69cc96258f907e9839f0d9)), closes [/github.com/browserify/node-util/issues/57#issuecomment-764436352](https://github.com//github.com/browserify/node-util/issues/57/issues/issuecomment-764436352)
* **db:** Identify retention and snooze mailboxes uniquely ([af20473](https://github.com/nextcloud/mail/commit/af20473aa7ddd6c80235d33535ad9a8eb14b2fa3))
* **db:** Run read-write-update of mailboxes in transaction ([f6154c5](https://github.com/nextcloud/mail/commit/f6154c528aadc43021987de967401682c9797563))
* deprecation warning for drafts controller test ([07c15f2](https://github.com/nextcloud/mail/commit/07c15f2b26cb5f97654f57d80df767d735faac91))
* **deps:** bump @ckeditor/ckeditor5-dev-utils from 33.0.1 to v34 ([96d13ae](https://github.com/nextcloud/mail/commit/96d13ae7941c667f1a37e3f7eb7cf47f0c369116))
* **deps:** bump @ckeditor/ckeditor5-dev-utils from 34.0.2 to ~34.1.2 ([86242da](https://github.com/nextcloud/mail/commit/86242da8c90991f32dc8e6a9d7bd19b51519a632))
* **deps:** bump @ckeditor/ckeditor5-dev-utils from 34.1.2 to ~34.1.3 ([85fa23a](https://github.com/nextcloud/mail/commit/85fa23a4c9ab6e672db1071e87067c94af2fb145))
* **deps:** bump @ckeditor/ckeditor5-dev-utils from 34.1.3 to v35 ([092a0dd](https://github.com/nextcloud/mail/commit/092a0dd1d77c8739cbfa175cbf4112db572284e5))
* **deps:** bump @ckeditor/ckeditor5-dev-utils from 35.0.3 to ~35.0.6 ([99f336f](https://github.com/nextcloud/mail/commit/99f336f1015def3a7c24e6d8d81d5f2f0bf6b721))
* **deps:** bump @ckeditor/ckeditor5-dev-utils from 35.0.6 to v37 ([d7822c1](https://github.com/nextcloud/mail/commit/d7822c12b2eec3c88764b98042332da3a4be449f))
* **deps:** bump @nextcloud/auth from 2.0.0 to ^2.1.0 ([d8855ce](https://github.com/nextcloud/mail/commit/d8855ce558315176129d9e2f6875921853a6eaff))
* **deps:** bump @nextcloud/auth from 2.1.0 to ^2.2.1 (main) ([#8887](https://github.com/nextcloud/mail/issues/8887)) ([a3d2a77](https://github.com/nextcloud/mail/commit/a3d2a77d44b047fd304e76ad7f1833f39d5e31d8))
* **deps:** bump @nextcloud/axios from 2.3.0 to ^2.4.0 ([eb29ab7](https://github.com/nextcloud/mail/commit/eb29ab77b38ef8b51c36cd6a94c6d587ac7d62ac))
* **deps:** bump @nextcloud/calendar-js from 3.2.0 to v5 ([bcee50a](https://github.com/nextcloud/mail/commit/bcee50a852bae25850ec802d5378fd978d28b7df))
* **deps:** bump @nextcloud/calendar-js from 5.0.3 to ^5.0.4 ([67d6099](https://github.com/nextcloud/mail/commit/67d6099971d5ba7fe619d95a36cb0e48ec8033f3))
* **deps:** bump @nextcloud/calendar-js from 5.0.4 to ^5.0.5 ([a3df9dc](https://github.com/nextcloud/mail/commit/a3df9dcd7f17f53699a615b8f17fb85ebe4072a8))
* **deps:** bump @nextcloud/calendar-js from 5.0.5 to v6 ([1bfce7e](https://github.com/nextcloud/mail/commit/1bfce7e8f794607e47b962ef83d499a40f52981d))
* **deps:** bump @nextcloud/calendar-js from 6.0.0 to ^6.0.1 ([9da3cb1](https://github.com/nextcloud/mail/commit/9da3cb105b419c8155888f1681935ae4cfe9427d))
* **deps:** bump @nextcloud/dialogs from 3.2.0 to v4 ([1086158](https://github.com/nextcloud/mail/commit/10861585a80b9d42955e196c167928a92ce6204b))
* **deps:** bump @nextcloud/dialogs from 4.0.1 to ^4.1.0 (main) ([#8662](https://github.com/nextcloud/mail/issues/8662)) ([8e05e5c](https://github.com/nextcloud/mail/commit/8e05e5c1bc6f7e5b7c41bf315484918281eedd8d))
* **deps:** bump @nextcloud/initial-state from 2.0.0 to ^2.1.0 (main) ([#8691](https://github.com/nextcloud/mail/issues/8691)) ([46f47ec](https://github.com/nextcloud/mail/commit/46f47ec7f94864caabcb27a69b2f851f84d2d105))
* **deps:** bump @nextcloud/l10n from 2.1.0 to ^2.2.0 (main) ([#8833](https://github.com/nextcloud/mail/issues/8833)) ([34a1ca0](https://github.com/nextcloud/mail/commit/34a1ca02a0ac7989b108fdb02311d2222bb92a6b))
* **deps:** bump @nextcloud/logger from 2.5.0 to ^2.7.0 (main) ([#8888](https://github.com/nextcloud/mail/issues/8888)) ([74b0576](https://github.com/nextcloud/mail/commit/74b05768d7f0caf5dac155552cffccbe51a56185))
* **deps:** bump @nextcloud/moment from 1.2.1 to ^1.2.2 (main) ([#8970](https://github.com/nextcloud/mail/issues/8970)) ([027069e](https://github.com/nextcloud/mail/commit/027069eb7ab742ae5fff34a8da9688b1bd69566d))
* **deps:** bump @nextcloud/router from 2.0.1 to ^2.1.1 ([83267ef](https://github.com/nextcloud/mail/commit/83267ef03d146e32bab35beb36424ddb1fdc82cd))
* **deps:** bump @nextcloud/router from 2.1.1 to ^2.1.2 ([4bcf00b](https://github.com/nextcloud/mail/commit/4bcf00bb21d3ad519245f17e114ffe946137143f))
* **deps:** bump @nextcloud/router from 2.1.2 to ^2.2.0 (main) ([#8999](https://github.com/nextcloud/mail/issues/8999)) ([0b9ccea](https://github.com/nextcloud/mail/commit/0b9ccea3abf7807faef10828ecbe03232900ff3e))
* **deps:** bump @nextcloud/vue from 7.11.3 to ^7.11.4 ([756b508](https://github.com/nextcloud/mail/commit/756b50801c04fe4627175a8469824775003f833f))
* **deps:** bump @nextcloud/vue from 7.11.4 to ^7.12.0 ([4f4c2e6](https://github.com/nextcloud/mail/commit/4f4c2e63835c8779fbd2baffcf834de5634388ae))
* **deps:** bump @nextcloud/vue from 7.12.0 to ^7.12.1 ([d4ee8a1](https://github.com/nextcloud/mail/commit/d4ee8a129a68a512d4f2762b4e709439e8e968a2))
* **deps:** bump @nextcloud/vue from 7.12.1 to ^7.12.2 ([2d6c7be](https://github.com/nextcloud/mail/commit/2d6c7be52b3214d151df19562fd9b7f44be35ac1))
* **deps:** bump @nextcloud/vue from 7.12.2 to ^7.12.4 ([ef1f9b5](https://github.com/nextcloud/mail/commit/ef1f9b51407c5108bb5a5bb796ebd319896268eb))
* **deps:** bump @nextcloud/vue from 7.12.4 to ^7.12.6 ([93d92ae](https://github.com/nextcloud/mail/commit/93d92ae227566595167cd989c65f2a899aa157cf))
* **deps:** bump @nextcloud/vue from 7.5.0 to ~7.8.0 ([93d1016](https://github.com/nextcloud/mail/commit/93d1016a48435a6fbcc9165da126dbe9556a8907))
* **deps:** bump @nextcloud/vue from 7.7.0 to ^7.7.1 ([6d012a3](https://github.com/nextcloud/mail/commit/6d012a3d0b316bd74e1f476280fbd68fb33bdf07))
* **deps:** bump @nextcloud/vue from 7.8.0 to ^7.8.5 ([59d1982](https://github.com/nextcloud/mail/commit/59d1982e7b74f417a69c03643e15986be77d3d9c))
* **deps:** bump @nextcloud/vue from 7.8.5 to ^7.11.3 ([ec4732b](https://github.com/nextcloud/mail/commit/ec4732bce76f6b1ea97e9f952f027459cf57acb0))
* **deps:** bump @nextcloud/vue-dashboard from 2.0.1 to ^2.0.1 ([f636457](https://github.com/nextcloud/mail/commit/f63645707ac4d6e5478039ffc7f31e50f7717d55))
* **deps:** bump arthurhoaro/favicon from 1.3.3 to ^1.3.4 (main) ([#8995](https://github.com/nextcloud/mail/issues/8995)) ([04fa7d0](https://github.com/nextcloud/mail/commit/04fa7d0d583b491c51cb9a87b357f03912904316))
* **deps:** bump arthurhoaro/favicon from 1.3.4 to v2 ([88a6bf7](https://github.com/nextcloud/mail/commit/88a6bf7f0aade4fb0a153c1e6553c498d19e806d))
* **deps:** bump bytestream/horde-imap-client from 2.32.0 to ^2.33.1 (main) ([#8862](https://github.com/nextcloud/mail/issues/8862)) ([4619663](https://github.com/nextcloud/mail/commit/4619663931caffae0c7da7eb7b0373b1ffcdafc1))
* **deps:** bump cerdic/css-tidy from 2.0.3 to v2.1.0 (main) ([#8889](https://github.com/nextcloud/mail/issues/8889)) ([d88c084](https://github.com/nextcloud/mail/commit/d88c0845b47eb1a41be6fea7f1f6556813584015))
* **deps:** bump christophwurst/kitinerary-bin from 0.3.0 to ^0.4 ([9e152bd](https://github.com/nextcloud/mail/commit/9e152bda26bc3d57c3e4c5b54b2fc0eaad74d9b6))
* **deps:** bump christophwurst/kitinerary-sys from 0.2.0 to ^0.2.1 ([185760b](https://github.com/nextcloud/mail/commit/185760b914a995d0996ae502f8e14043140896a4))
* **deps:** bump ckeditor family from 35.1.0 to v35.4.0 ([ecf545c](https://github.com/nextcloud/mail/commit/ecf545cf9d77c3d73512b4f07c522c9d33837785))
* **deps:** bump ckeditor family from 35.4.0 to v37 ([baa8e33](https://github.com/nextcloud/mail/commit/baa8e33157f57fce5dec66f4e419567e74ff005a))
* **deps:** bump ckeditor family from 37.0.1 to v37.1.0 ([ada9892](https://github.com/nextcloud/mail/commit/ada9892b482c18972cbe291ff2991a67b586122f))
* **deps:** bump core-js from 3.28.0 to ^3.29.0 ([e9fdca5](https://github.com/nextcloud/mail/commit/e9fdca5c6969ac6fc25f8722e2c121c12a3bf6b5))
* **deps:** bump core-js from 3.29.0 to ^3.29.1 ([0ae1b80](https://github.com/nextcloud/mail/commit/0ae1b809645babbe8a881863def49048c2444f98))
* **deps:** bump core-js from 3.29.1 to ^3.30.1 ([a19e752](https://github.com/nextcloud/mail/commit/a19e7528a8b02819debde15b0013a590ee91dc66))
* **deps:** bump core-js from 3.30.1 to ^3.30.2 ([b1f8354](https://github.com/nextcloud/mail/commit/b1f8354aa099f8bfcbc10bbc4960e5525b81f56c))
* **deps:** bump core-js from 3.30.2 to ^3.31.0 (main) ([#8542](https://github.com/nextcloud/mail/issues/8542)) ([3f5cb32](https://github.com/nextcloud/mail/commit/3f5cb32ddec5c767ab647cc17c811409ab499cf6))
* **deps:** bump core-js from 3.31.0 to ^3.31.1 (main) ([#8619](https://github.com/nextcloud/mail/issues/8619)) ([a204a78](https://github.com/nextcloud/mail/commit/a204a789a0f43e09c12f9133aee5937f56fc812c))
* **deps:** bump core-js from 3.31.1 to ^3.32.2 (main) ([#8877](https://github.com/nextcloud/mail/issues/8877)) ([cd75e3c](https://github.com/nextcloud/mail/commit/cd75e3c3170564c4675c6a7a1b89a69032aa9189))
* **deps:** bump core-js from 3.32.2 to ^3.33.0 (main) ([#8921](https://github.com/nextcloud/mail/issues/8921)) ([9591a27](https://github.com/nextcloud/mail/commit/9591a27f717b6b84a15e96c172aa52bba3fda20c))
* **deps:** bump core-js from 3.33.0 to ^3.33.1 (main) ([#8996](https://github.com/nextcloud/mail/issues/8996)) ([1e1e05d](https://github.com/nextcloud/mail/commit/1e1e05d871ea92b2cb427a236ea1df97ef7b1484))
* **deps:** bump core-js from 3.33.1 to ^3.33.2 (main) ([#9039](https://github.com/nextcloud/mail/issues/9039)) ([9f8935f](https://github.com/nextcloud/mail/commit/9f8935f9c8dca1ec1af3a2bbc8435a8a42481b30))
* **deps:** bump dompurify from 2.4.4 to ^2.4.5 ([4666628](https://github.com/nextcloud/mail/commit/466662877a31a3ad9ed2fa69b1eb7cd8a6125e6a))
* **deps:** bump dompurify from 2.4.5 to v3 ([aac1a47](https://github.com/nextcloud/mail/commit/aac1a47b8e3c6b308bf331ab1077283133eb04b6))
* **deps:** bump dompurify from 3.0.1 to ^3.0.2 ([6b5e91f](https://github.com/nextcloud/mail/commit/6b5e91fbeae6539a86e1bcf59381fff19ba019fc))
* **deps:** bump dompurify from 3.0.2 to ^3.0.3 ([36b38fc](https://github.com/nextcloud/mail/commit/36b38fcdee9d1bc54481515d844a2f2a8733e354))
* **deps:** bump dompurify from 3.0.3 to ^3.0.5 (main) ([#8600](https://github.com/nextcloud/mail/issues/8600)) ([bd916f4](https://github.com/nextcloud/mail/commit/bd916f44b1e5efaa73f70b2a37f9270e7565b927))
* **deps:** bump dompurify from 3.0.5 to ^3.0.6 (main) ([#8913](https://github.com/nextcloud/mail/issues/8913)) ([520b22b](https://github.com/nextcloud/mail/commit/520b22bd744086c22c7934ea29ba1fd6f9ca2403))
* **deps:** bump html-to-text from 9.0.4 to ^9.0.5 ([bb50bd3](https://github.com/nextcloud/mail/commit/bb50bd3ee49028ee9882bcad5ddd1b59e94edcaf))
* **deps:** bump iframe-resizer from 4.3.4 to ^4.3.5 ([292a4ed](https://github.com/nextcloud/mail/commit/292a4eda417ec841b61874db5b3ceb0c12f67c4a))
* **deps:** bump iframe-resizer from 4.3.5 to ^4.3.6 ([1258c20](https://github.com/nextcloud/mail/commit/1258c204a8b18b5454b41654089d73075fa6e108))
* **deps:** bump iframe-resizer from 4.3.6 to ^4.3.7 (main) ([#8876](https://github.com/nextcloud/mail/issues/8876)) ([a0e1b75](https://github.com/nextcloud/mail/commit/a0e1b758a5b7b6e3aae9d96c2a2292c0446cf47f))
* **deps:** bump ramda from 0.28.0 to ^0.29.0 ([dc1b797](https://github.com/nextcloud/mail/commit/dc1b797b2e1bd9320b73d3725299c85e3636adde))
* **deps:** bump ramda from 0.29.0 to ^0.29.1 ([a7da9c5](https://github.com/nextcloud/mail/commit/a7da9c57c8666f0192f1fa68f0358e9821263b2b))
* **deps:** bump rubix/ml from 2.3.0 to v2.3.1 ([3f3b8ab](https://github.com/nextcloud/mail/commit/3f3b8abf08855042ff04d779607f30584597dd76))
* **deps:** bump rubix/ml from 2.3.1 to v2.3.2 ([b1d0c39](https://github.com/nextcloud/mail/commit/b1d0c39f0e061caf8d15481f5f67326c5fc428ec))
* **deps:** bump rubix/ml from 2.3.2 to v2.4.0 (main) ([#8878](https://github.com/nextcloud/mail/issues/8878)) ([2bf6339](https://github.com/nextcloud/mail/commit/2bf6339760d4d654883f0f4768ed3a3567755f1c))
* **deps:** bump stylelint from 14.16.1 to v15 ([58cc279](https://github.com/nextcloud/mail/commit/58cc279cc100ebe7a146f970b912839a6f79f608))
* **deps:** bump stylelint from 15.10.1 to ^15.10.2 (main) ([#8652](https://github.com/nextcloud/mail/issues/8652)) ([55071f1](https://github.com/nextcloud/mail/commit/55071f1bfcb319b93be867e2a9993fe945eca7ba))
* **deps:** bump stylelint from 15.10.2 to ^15.10.3 (main) ([#8794](https://github.com/nextcloud/mail/issues/8794)) ([19fb7d1](https://github.com/nextcloud/mail/commit/19fb7d13a55f29a4de1ac60ffacfcbfca1662a75))
* **deps:** bump stylelint from 15.10.3 to ^15.11.0 (main) ([#8972](https://github.com/nextcloud/mail/issues/8972)) ([029a85e](https://github.com/nextcloud/mail/commit/029a85ec6283b5a5f64d8109ba3a086c24213105))
* **deps:** bump stylelint from 15.6.0 to ^15.6.1 ([bb01248](https://github.com/nextcloud/mail/commit/bb012480e1bcc746f2410f6a2c41241609839eb9))
* **deps:** bump stylelint from 15.6.1 to ^15.6.2 ([36919f8](https://github.com/nextcloud/mail/commit/36919f817dc88d104aa7efd63ab571fae7024ab9))
* **deps:** bump stylelint from 15.6.2 to v15.10.1 ([03a1960](https://github.com/nextcloud/mail/commit/03a19607efa299c92c51cbb53df7d75e0392db7f))
* **deps:** bump uuid from 9.0.0 to ^9.0.1 (main) ([#8861](https://github.com/nextcloud/mail/issues/8861)) ([c127802](https://github.com/nextcloud/mail/commit/c1278020abf4b0b2dc42687913d527c02f889279))
* **deps:** bump vue monorepo from 2.7.14 to ^2.7.15 (main) ([#8998](https://github.com/nextcloud/mail/issues/8998)) ([048cfcf](https://github.com/nextcloud/mail/commit/048cfcf180064f8b02fb5be08776055bc4b91e56))
* **deps:** bump webdav from 4.11.2 to ^4.11.3 (main) ([#8832](https://github.com/nextcloud/mail/issues/8832)) ([6a3eb86](https://github.com/nextcloud/mail/commit/6a3eb86ac548e23d7dd032c2553873c8848f0661))
* **deps:** bump webpack to v5.76.0 ([283751c](https://github.com/nextcloud/mail/commit/283751c36c014f0f550a9324870f0f10b7aa5dc2))
* **deps:** pin @ckeditor/ckeditor5-dev-utils from 37.0.1 to 37.0.1 ([97d3f99](https://github.com/nextcloud/mail/commit/97d3f99dcc5a75f1b9d6d71553db877ea5d6dd94))
* **deps:** pin dependencies ([04f90aa](https://github.com/nextcloud/mail/commit/04f90aac8fa8e3406c5678e1176a5eccc167c9cc))
* **deps:** update dependency @ckeditor/ckeditor5-dev-utils to ~33.0.1 ([b6c05fb](https://github.com/nextcloud/mail/commit/b6c05fb41716eecefd6edb7a74907e82c30030bf))
* **deps:** update dependency @ckeditor/ckeditor5-dev-webpack-plugin to ~31.1.13 ([1a6df4d](https://github.com/nextcloud/mail/commit/1a6df4dccbeb528935b57b7823554330cde584c6))
* **deps:** update dependency @nextcloud/calendar-js to ^3.2.0 ([34e890f](https://github.com/nextcloud/mail/commit/34e890f6b140b0d660eb063e54abf5d539aa430c))
* **deps:** update dependency @nextcloud/l10n to ^2.1.0 ([9e02ddf](https://github.com/nextcloud/mail/commit/9e02ddf783442d446081c749e670ac8896b35e67))
* **deps:** update dependency @nextcloud/logger to ^2.5.0 ([63fa8fc](https://github.com/nextcloud/mail/commit/63fa8fcd18fd3be24d59fc8877a52af33956dbf5))
* **deps:** update dependency @nextcloud/router to ^2.0.1 ([640cb16](https://github.com/nextcloud/mail/commit/640cb16318de8b66d8594983696d0326eb04bdc5))
* **deps:** update dependency arthurhoaro/favicon to ^1.3.3 ([3dd40d9](https://github.com/nextcloud/mail/commit/3dd40d9c983d230257c26033d56c609a885b2834))
* **deps:** update dependency bamarni/composer-bin-plugin to ^1.8.2 ([72a6c3d](https://github.com/nextcloud/mail/commit/72a6c3da4051f6fbe80dcf5f9fbca60e8e6a7902))
* **deps:** update dependency bytestream/horde-exception to ^2.2.0 ([c6a22fb](https://github.com/nextcloud/mail/commit/c6a22fb5145f96cc6f0f1583b4a7712fe76dd5aa))
* **deps:** update dependency bytestream/horde-imap-client to ^2.32.0 ([fd710ca](https://github.com/nextcloud/mail/commit/fd710caa1bc5c8e1d0273c50ad1af779e3779262))
* **deps:** update dependency bytestream/horde-mail to ^2.7.1 ([e7f928a](https://github.com/nextcloud/mail/commit/e7f928aa0445e360a6c8148d3cd07b87c920b285))
* **deps:** update dependency bytestream/horde-mime to ^2.13.0 ([9ba0faa](https://github.com/nextcloud/mail/commit/9ba0faaf21c7a488d7bd68b659d0bf0792ae4b57))
* **deps:** update dependency bytestream/horde-stream to ^1.7.1 ([32dd650](https://github.com/nextcloud/mail/commit/32dd6505b093c91c688231f6297147d2f6462220))
* **deps:** update dependency bytestream/horde-stringprep to ^1.2.1 ([6899089](https://github.com/nextcloud/mail/commit/6899089b54536f6887ac0ed8fb0bfd85c64e3dbe))
* **deps:** update dependency bytestream/horde-support to ^2.4.0 ([0815416](https://github.com/nextcloud/mail/commit/0815416339873da5fcac33742d2e9564311a71c4))
* **deps:** update dependency bytestream/horde-text-filter to ^2.5.0 ([c3e551a](https://github.com/nextcloud/mail/commit/c3e551ad07f1cfd0bae89b503526807c8d7dab5f))
* **deps:** update dependency bytestream/horde-util to ^2.7.0 ([3189a16](https://github.com/nextcloud/mail/commit/3189a1662e5fc3473cb517a0bdc94d45773d154c))
* **deps:** update dependency core-js to ^3.28.0 ([6024f02](https://github.com/nextcloud/mail/commit/6024f025b649fe714a36cd4544c706868e8cf403))
* **deps:** update dependency dompurify to ^2.4.4 ([f5e1f7a](https://github.com/nextcloud/mail/commit/f5e1f7a322fe3324342f12a92c6df41352713950))
* **deps:** update dependency html-to-text to ^9.0.4 ([8cd5302](https://github.com/nextcloud/mail/commit/8cd53027c1e893bcb5f3dc32cc03b3ccd8a85943))
* **deps:** update dependency html2text/html2text to ^4.3.1 ([e7c6575](https://github.com/nextcloud/mail/commit/e7c65752296babeb30fb2ffce4d9be7755cff9e4))
* **deps:** update dependency iframe-resizer to ^4.3.4 ([b0b5368](https://github.com/nextcloud/mail/commit/b0b536802116bc562b89b9e849253b34c98b0427))
* **deps:** update dependency js-base64 to ^3.7.5 ([e0d0502](https://github.com/nextcloud/mail/commit/e0d0502a0f6c943022987ebafb70dd971557a0b0))
* **deps:** update dependency nextcloud/horde-smtp to ^1.0.2 ([de218de](https://github.com/nextcloud/mail/commit/de218de8faf08072fd032ab7516efbd07f3b1969))
* **deps:** update dependency sabberworm/php-css-parser to ^8.4.0 ([18dc8ca](https://github.com/nextcloud/mail/commit/18dc8cacc7d8d2d1c20f29e89a3f377276be0550))
* **deps:** Update voku/* to fix PHP8.2 warnings ([5b816be](https://github.com/nextcloud/mail/commit/5b816be463bfb11b6306b33f89bb293930028150))
* **deps:** Update wamania/php-stemmer and voku/portable-utf8 ([f176956](https://github.com/nextcloud/mail/commit/f176956aff7d299dc148b4865fccc0792be99556))
* downgrade nextcloud/vue to 7.8.0 to fix envelope list ([7c7da66](https://github.com/nextcloud/mail/commit/7c7da66a3704846de3b5ab29ab3ba1f5d2fc3ce5))
* **drafts:** delete old draft when saving new version ([d7a9370](https://github.com/nextcloud/mail/commit/d7a93709b8af95a1a153f805bf518bb4c498c201))
* drop index on mail_messages_retention.message_id ([a884ebf](https://github.com/nextcloud/mail/commit/a884ebf2ea187c78c2d8a0837bc848c15a8b42e9))
* drop unique index on mail_messages_snoozed.message_id ([d7e397e](https://github.com/nextcloud/mail/commit/d7e397e5844d47a23bb8cfa339560b76ac995049))
* endless loop on mailbox initialization ([0048e40](https://github.com/nextcloud/mail/commit/0048e4002cf6c91680c8855b2a41c91708d85e1c))
* **envelope menu:** prevent modals from closing automatically ([b0209f1](https://github.com/nextcloud/mail/commit/b0209f1aae8190812fa07ed275d44fd80e352c08))
* handle attachments without transfer encoding properly ([8c3c248](https://github.com/nextcloud/mail/commit/8c3c248c585ba8da036cd8bc3cc2cdfc79ad02f3))
* handling of envelope fetch error messages ([d4a5e8e](https://github.com/nextcloud/mail/commit/d4a5e8e9c697ad27c0c63f81e2872d33cfef3cb1))
* Harden outbox/draft message retrieval of shared storage ([2eaa6eb](https://github.com/nextcloud/mail/commit/2eaa6eb145a6f856c402d71d4fe61869815ec6f3))
* html-entitites via mb_convert_encoding was deprecated with php 8.2 ([71490a0](https://github.com/nextcloud/mail/commit/71490a0b02bbc0fd8844a5c39d44e609f4448986))
* **i19n:** Changed grammar ([7a36cdb](https://github.com/nextcloud/mail/commit/7a36cdbbd2c02fa7254a6d53e28715bf827daec5))
* **imap:** Chunk MessageMapper::findByIds by command length ([768d8f8](https://github.com/nextcloud/mail/commit/768d8f8ffc505c587686a13d97dbef017aaf23c7))
* **imap:** Chunk UIDs by string length ([df9e386](https://github.com/nextcloud/mail/commit/df9e386bdd4e9b1d483acd07a94a9b04c6ac5dc2))
* **imap:** Ignore no select mailboxes for MYRIGHTS ([426d51f](https://github.com/nextcloud/mail/commit/426d51f105d4103435a0a0875e25a0a635d33adf))
* **imap:** Ignore non existent mailboxes (again) ([e5e6402](https://github.com/nextcloud/mail/commit/e5e640209fb140e6ff97b7bfaccd704801563764))
* **imap:** Log exception of failed namespace fetch ([74159b2](https://github.com/nextcloud/mail/commit/74159b28a631567e6aa4582c09d84551e3a45ae1))
* **imap:** Only fetch mailbox STATUS once ([9582004](https://github.com/nextcloud/mail/commit/9582004b27320a73ffe701e3fba6db9a2354ace3))
* **mailbox cache:** Fix mailbox cache sync scope of current mailbox ([4d7c52d](https://github.com/nextcloud/mail/commit/4d7c52dc7069f7b940da31166cdfbede836a3fca))
* **mailto:** show empty thread view on handler ([43bdb38](https://github.com/nextcloud/mail/commit/43bdb38ee501d3e884ac275b5dba4141643d1627))
* **mailvelope:** hide the missing pgp key warning when mailevelope is disabled again ([ef2481f](https://github.com/nextcloud/mail/commit/ef2481f8b7bf7124fafc08ee4e36a5902828693f))
* make nested toolbar working for signature editor ([8c0965c](https://github.com/nextcloud/mail/commit/8c0965c775ab672cf1a2c24f161bad35da71da4c))
* Make no sent mailbox configurged exception readable ([7109f99](https://github.com/nextcloud/mail/commit/7109f998fa34e1920b917c1fb1e8859b957ba112))
* Merge overlapping recipient popovers ([c2925f1](https://github.com/nextcloud/mail/commit/c2925f143385f6a7e0f76a3108e4173c7e1ef7a0))
* **message-filter:** Show starred messages in Favourites again ([465d4e5](https://github.com/nextcloud/mail/commit/465d4e5d52d5cc847523641b554911095d2011cd))
* move to.exact route option to a prop ([a10117c](https://github.com/nextcloud/mail/commit/a10117ced60837c8993f1e4f1b0a84a81f0d6f9d))
* **outbox:** select correct account/alias when opening messages ([2471880](https://github.com/nextcloud/mail/commit/247188068f2c364939de10bfce92d25a93c590eb))
* **php:** Fix ProvisioningMiddleware method return type ([aae086c](https://github.com/nextcloud/mail/commit/aae086cbcb408764d5c8279e17d4fe87f358248d))
* Position thread envelope icons relative to avatar ([ae619aa](https://github.com/nextcloud/mail/commit/ae619aa46b950858b4bcff0ad545a4955f0c8d3e))
* **preprocessing:** chunk message query ([62a1c98](https://github.com/nextcloud/mail/commit/62a1c98a83677b937dff8f890acd9db74e5c349a))
* **provisioning:** Clear cache before returning ([961533d](https://github.com/nextcloud/mail/commit/961533db6b7d7633638d52cc862b638830929e64))
* **provisioning:** Clear config cache after every mutation ([b6f7073](https://github.com/nextcloud/mail/commit/b6f707350de39c07728ab46f84e73d16c52fdc17))
* **provisioning:** Return database ID of new configs ([81f35a2](https://github.com/nextcloud/mail/commit/81f35a2990c52c9aeda5b53ed48f34044a0d1beb))
* **proxy:** Add image proxy rate limit ([18f886c](https://github.com/nextcloud/mail/commit/18f886c37bd48db00087143ae012e37405559c70))
* **quota:** Rename placeholder and fix desktop notification ([cc87c54](https://github.com/nextcloud/mail/commit/cc87c54f02f6d084e7f3bd1ee7f1b3eecc15e515))
* **Quota:** set job time insensitive and lower interval ([0e7f635](https://github.com/nextcloud/mail/commit/0e7f63572d47f6db021b22a333c29e0cff6ef951))
* Rate-limit IMAP auth if the password is wrong ([6528dec](https://github.com/nextcloud/mail/commit/6528dec049e31b7718b9b03d08f0d672c84fb695))
* regressions from ACL pulls ([6cde824](https://github.com/nextcloud/mail/commit/6cde82485907ddeb37c8edea497ebee9bd22fc45))
* **retention:** properly clean orphans ([c99577d](https://github.com/nextcloud/mail/commit/c99577dccbccd39725dc29aba19e7b7c84ec454c))
* Revert empty array checks in Horde Cache ([b780e7e](https://github.com/nextcloud/mail/commit/b780e7ea30f9db70c195d89a0de7307dc15523df))
* saving preferences not extracting the returned value properly ([abe3205](https://github.com/nextcloud/mail/commit/abe3205d81848faeed9d95d63685a4289cd8e570))
* **search:** Fix combining IMAP and DB search results ([3aabd7a](https://github.com/nextcloud/mail/commit/3aabd7a2a369ba4ab6f766d501045e959bdc6d5f))
* **search:** Limit recipient joins to their types ([1bbb44b](https://github.com/nextcloud/mail/commit/1bbb44b6a888c697e5e241cd8abffa694f53c844))
* **search:** URL-encode all parameters to preserve special characters ([1ef331c](https://github.com/nextcloud/mail/commit/1ef331c2707a1cf04183340ad78c067b3fbc4097))
* **search:** Use corresponding table alias for recipient search ([2270a93](https://github.com/nextcloud/mail/commit/2270a931b5af7f2902b256a1471b2a251a917bfb))
* **sender-details:** show contact names again ([b448594](https://github.com/nextcloud/mail/commit/b4485947b642af3b3751372ed0382808496db975))
* **settings:** fix account settings modal close button ([dfbfaa3](https://github.com/nextcloud/mail/commit/dfbfaa3de38c5faf5a3040db72466ed2e0820c2d))
* **setup:** Fix sending password for OAUTH accounts ([871ffc9](https://github.com/nextcloud/mail/commit/871ffc9b890555718d126b8e896ed0072acac72e))
* **setup:** Fix storing password when we don't expect one ([964f665](https://github.com/nextcloud/mail/commit/964f665f1a935896a41f31b49916ad0facbc42b7))
* **setup:** Rate limit auto config attempts ([a82b8ab](https://github.com/nextcloud/mail/commit/a82b8ab9cba4b17c0fdcd0a4ae4b2ac7744692cd))
* share enumeration constraints on autocomplete ([1f4ad67](https://github.com/nextcloud/mail/commit/1f4ad67c6a23f053a8b8a3a3c669eabaf1753f49))
* **sieve:** load sieve scipt on open settings ([5a7a04f](https://github.com/nextcloud/mail/commit/5a7a04f69f4ba8025bef4d68c64967c70eb2419e))
* **sieve:** show feedback on syntax error ([3443da7](https://github.com/nextcloud/mail/commit/3443da78be8186fd12a1af94227afe607ef9fb25))
* **smime:** add missing primary key to certificate table ([59e7728](https://github.com/nextcloud/mail/commit/59e77285e31e1d71f69763171e473dd7fed48b77))
* **smime:** add missing ReturnTypeWillChange annotation ([6607f4d](https://github.com/nextcloud/mail/commit/6607f4df966a1557ac69c3dc4189e616fc0c9768))
* **smime:** handle certificates with no emailAddress field ([3490178](https://github.com/nextcloud/mail/commit/3490178e3a5a2f96f383da297bcedf0bb83c789e))
* **smime:** handle PKCS12 stores with multiple certificates ([1c445d0](https://github.com/nextcloud/mail/commit/1c445d0070e3562afff085fa302901305db8d0be))
* **smime:** set primary key in first migration ([3a19298](https://github.com/nextcloud/mail/commit/3a1929879ad5fe190f6180c0d271ac9272e65e49))
* **snooze:** Add cleanup orphan db entries ([bd184dd](https://github.com/nextcloud/mail/commit/bd184ddba9badf7cb808ee1143989b08996f1427))
* **Snooze:** Add unsnooze action ([3f5f9dd](https://github.com/nextcloud/mail/commit/3f5f9ddd4ab193d0790a6bc88fd13c42f38ed17d))
* **Snooze:** Allow snoozing gmail messages ([0a98404](https://github.com/nextcloud/mail/commit/0a984048ccdac67ac8bd0be0dd2e1a155ebd5bbd))
* **snooze:** create snooze mailbox on first snooze ([d38e426](https://github.com/nextcloud/mail/commit/d38e4265d6de3f9d38fc386bedfc08cd8be4b733))
* **snooze:** force sync of snooze mailbox ([4afdafc](https://github.com/nextcloud/mail/commit/4afdafcf1c802fff1b119b1ad073a8f4c1425c95))
* **Snooze:** Move the message back to src mailbox on wake ([c5e2bee](https://github.com/nextcloud/mail/commit/c5e2bee1e463b2f8c2986837e939e42ec282c357))
* **Snooze:** some minor changes ([326d48f](https://github.com/nextcloud/mail/commit/326d48fd85a2e6cafe6b27f63f0fb48976ae162f))
* **Snooze:** Sort snooze mailbox as specialUse ([ad6b723](https://github.com/nextcloud/mail/commit/ad6b723e18f16c879b47d5dd051d0fe67718bb98))
* **strings:** Explicit set/unset tag in TagModal ([51bc6a2](https://github.com/nextcloud/mail/commit/51bc6a2b221f7c7028079e0ad1584371357d6b94))
* **strings:** Placeholders in SearchMessage comp ([80f7108](https://github.com/nextcloud/mail/commit/80f710835fa546105150de6aad5b8c4fe2612ea9))
* **sync:** mailboxes not being synced due to short circuiting ([7c2c806](https://github.com/nextcloud/mail/commit/7c2c8063c8f7a4abb57ceb5e351efe1e8fe6e882))
* **sync:** return if headers couldn't be parsed ([998bf34](https://github.com/nextcloud/mail/commit/998bf34b5c6f36d46c5e96237a79b77d9a09c737))
* **tags:** Fix multiple tags with same label ([3773d0b](https://github.com/nextcloud/mail/commit/3773d0bcd263ac871d4bc518b4c18df6ecb2993b))
* **tags:** Hide "Has Cal" tag ([87d3aa3](https://github.com/nextcloud/mail/commit/87d3aa3a54265e4aed44e0b561974c8a9a805a50))
* **unsubscribe:** fix button style ([9142ada](https://github.com/nextcloud/mail/commit/9142ada5224cb23305f498cc709fd29395617471))
* update interface to match implementation ([55f6c7e](https://github.com/nextcloud/mail/commit/55f6c7e131fa43eb9adde08df9343b2aa3cbb048))
* update mocked time for new job interval ([1b83193](https://github.com/nextcloud/mail/commit/1b8319324eb1adcbb96c8f40d908763d483e109b))
* use api to read input value ([89b3f9c](https://github.com/nextcloud/mail/commit/89b3f9ccc00dba84d7adb84684ed44f72749fe10))
* **XOAUTH2:** Defer OAUTH account detection ([df69d19](https://github.com/nextcloud/mail/commit/df69d19ed22a6c7613c1843d931398c62c12b739))


### Features

* **acl:** check delete acl for move operation ([8d7a80b](https://github.com/nextcloud/mail/commit/8d7a80b8b1908244daa98b67f08f7eb7d46010ae))
* **acl:** respect acl for MailboxInlinePicker ([83ff894](https://github.com/nextcloud/mail/commit/83ff89433031be3b0ea5953e302f9d88a2ef7b6d))
* **acl:** Use shared folder icon for shared mailboxes ([c0687bf](https://github.com/nextcloud/mail/commit/c0687bf5f885e35f56a4ebc75cdd0c8520866eb0))
* add snooze mvp ([e16b6a7](https://github.com/nextcloud/mail/commit/e16b6a75a4f5e24deacaa5a278d32f65062746f9))
* add utility mailboxHasRights ([08d08bb](https://github.com/nextcloud/mail/commit/08d08bb6ba888b7277f8030f98d25316587a3e29))
* Allow a configurable background sync interval ([89e009e](https://github.com/nextcloud/mail/commit/89e009ea22f1800008a9f12122d1b6392c5d2954))
* **autoresponder:** implement subject placeholder ([42cae42](https://github.com/nextcloud/mail/commit/42cae425b6217c1173662db6aa1d3ca040a3f4a1)), closes [#7216](https://github.com/nextcloud/mail/issues/7216)
* **classification:** refactor IExtractor::extractor ([1790f01](https://github.com/nextcloud/mail/commit/1790f01ba89d53e30730ce6bfc74e4db8d70d963))
* compatibility layer for legacy exceptions ([118671f](https://github.com/nextcloud/mail/commit/118671f4d5d85f794f413592509cb7b23615777c))
* delete tags ([50f0772](https://github.com/nextcloud/mail/commit/50f07725796317b00928c47e217f2c235eb37d69))
* **deps:** Add Nextcloud 27 support ([aa32a2c](https://github.com/nextcloud/mail/commit/aa32a2c0b0fccead9cfb045085095bf0b3fc7613))
* **deps:** Add Nextcloud 28 support ([6cc4d93](https://github.com/nextcloud/mail/commit/6cc4d930f935a717b505d1245ff41abaf92fab0e))
* Easy unsubscribe from lists with http unsubscribe header ([491970a](https://github.com/nextcloud/mail/commit/491970abb12da520426b7a755f018281499bd35d))
* Easy unsubscribe from lists with mailto unsubscribe header ([dc30fa6](https://github.com/nextcloud/mail/commit/dc30fa60adc173993c82436c24e71b184d5bc7f4))
* exclude envelopes from junk from threading view ([4ddd3f6](https://github.com/nextcloud/mail/commit/4ddd3f635b10d082a39347c7d407fbbbd171e2af))
* hide mailboxes when the user is not allowed to move something there ([fdca17f](https://github.com/nextcloud/mail/commit/fdca17fba11943da382cd8b56aac673fa5fc50cb))
* **imap:** Persist if mailbox is shared or not ([4c27112](https://github.com/nextcloud/mail/commit/4c271120ff564c1ff9e728465c3d82bf92fa57c4))
* implement trash retention ([de09050](https://github.com/nextcloud/mail/commit/de09050a3a03a3a762250566baa1c99ca400cf58))
* load active sieve script on demand ([1a7fbc3](https://github.com/nextcloud/mail/commit/1a7fbc39fc791b175659250a5e24ed6a843dd418))
* **mailbox sharing:** Read, cache and expose my ACL rights ([76d7d7c](https://github.com/nextcloud/mail/commit/76d7d7c9564000c9c52e7c43f7897458f326f34e))
* move messages to junk folder ([45c5ab9](https://github.com/nextcloud/mail/commit/45c5ab9277ba50b2499ca05e7bf3e568d3ea1a44))
* **occ:** Add ML prediction command ([98607e2](https://github.com/nextcloud/mail/commit/98607e26237d7c58ec5156421697b6a8ba3e7956))
* One-click unsubscribe ([f2b61b3](https://github.com/nextcloud/mail/commit/f2b61b383b95cecaafd2fc078aa91999d8feb8f2))
* parse mailto addresses ([6261efc](https://github.com/nextcloud/mail/commit/6261efcf2feffd37e0b11c6f2bc992235b9a0411))
* plugin to insert smart picker links ([f34f69b](https://github.com/nextcloud/mail/commit/f34f69b3ab8d0be424cad49afbd70e8a0b93fd04))
* remove cast to int ([fa5830b](https://github.com/nextcloud/mail/commit/fa5830b43af6bbea2622e5837d39a46aba1a4630))
* remove toggle for move junk ([fd1437b](https://github.com/nextcloud/mail/commit/fd1437b7a2252e6cf7f62ac5775f2bc3e3f019fa))
* Rework draft handling front-end ([f1f757e](https://github.com/nextcloud/mail/commit/f1f757e57bcf1fce74fa202c8e261eceb79a0dd4))
* **search:** Match recipient labels too ([102a070](https://github.com/nextcloud/mail/commit/102a07060619432324729d7a50a56f7c5d5dfb91))
* **search:** Use case-independent wildcard matches for recipients ([8955638](https://github.com/nextcloud/mail/commit/89556386b4a21fe37d879f93735caa95500342fe))
* send multiple flags in a single request ([95319c0](https://github.com/nextcloud/mail/commit/95319c043bbafa32228b69fc0fa8626d056f31e8))
* set app name for mail ([8005431](https://github.com/nextcloud/mail/commit/8005431d91a37d47223fbcd6a65a19790c6af6c1))
* **settings:** add title to account settings modal ([9bd0f9f](https://github.com/nextcloud/mail/commit/9bd0f9f6bb8a43855cf0a885f6cac542984ce1af))
* **smime:** decrypt incoming messages ([6fcb907](https://github.com/nextcloud/mail/commit/6fcb90796f4d0088d576aa56ada50bcc36b6ffc0))
* **smime:** encrypt messages ([9c568b5](https://github.com/nextcloud/mail/commit/9c568b5e25d6f3eaf09d556e09825d5983d02077))
* **smime:** import pkcs12 certificates ([5a1428a](https://github.com/nextcloud/mail/commit/5a1428ac3023ad93deedadf6565a8d6d8a643cbd))
* **smime:** import smime certificates ([4c35589](https://github.com/nextcloud/mail/commit/4c35589f7cd52ca24cfec3b5630fbdc43d67e81d))
* **smime:** show a warning when a signature is not verified ([e305db8](https://github.com/nextcloud/mail/commit/e305db89ac0b0480cd66cf105ec8dc6925700f73))
* **smime:** sign outgoing emails ([c0db433](https://github.com/nextcloud/mail/commit/c0db433d75ec01b95fcbf629d6e6ceaf43ab57ec))
* **smime:** verify signature of encrypted messages ([1efd801](https://github.com/nextcloud/mail/commit/1efd8013bcb241df128b1a5894308abe44e18edd))
* **smime:** verify signed messages ([3ab22fa](https://github.com/nextcloud/mail/commit/3ab22fade847175938b4a39590bb6c63100461e0))
* **snooze:** Disable snooze on AJAX cron ([49725af](https://github.com/nextcloud/mail/commit/49725af3a9365237168a15d4d11f3ab291d69bdd))
* **unsubscribe:** validate dkim signature for one click unsubscribe ([448dabd](https://github.com/nextcloud/mail/commit/448dabd64427483f4bc3068c133ef20263e3ef6e))
* use proper json response for getDkim ([35d824d](https://github.com/nextcloud/mail/commit/35d824dca070218bd492f118af431b9e841bff3f))


### Performance Improvements

* **autoloader:** Use Composer's authoritative classmap ([1e28bc6](https://github.com/nextcloud/mail/commit/1e28bc60db0c0c3dac537b2422d37185299c2d4e))
* **dashboard:** implement widget item api v2 ([19f21af](https://github.com/nextcloud/mail/commit/19f21af9914bc7cd338dc37e391bea75fb272aa3))
* **frontend:** Load NewMessageModal async ([ab26e4b](https://github.com/nextcloud/mail/commit/ab26e4bb359c5fe9edd3f167ee0dd3e097904723))
* **frontend:** Load the account settings modal async ([f7949b3](https://github.com/nextcloud/mail/commit/f7949b32320fc86b203a5ea8484deb00a9c0e907))
* **imap:** Reduce number of STATUS commands for background mailbox sync ([f4b01d9](https://github.com/nextcloud/mail/commit/f4b01d9fa8adee1825abcc1c3a32268dc98c4856))
* **PHP:** Use static closures where possible ([e02a205](https://github.com/nextcloud/mail/commit/e02a2051f517a10b9f0e521ce03759ed6652a3e7))


### Reverts

* Revert "improve message preview" ([04f1842](https://github.com/nextcloud/mail/commit/04f1842f105e4a57a4fdd9d16db4f5743dd054ce))
* Revert "fix(deps): update dependency @nextcloud/vue to ^7.7.0" ([a5568e2](https://github.com/nextcloud/mail/commit/a5568e2cd49a8ef4ceb59dc6ee11f3bbff56b182))
* Revert "chore(deps): Enable renovate bumps on main" ([255efb7](https://github.com/nextcloud/mail/commit/255efb7902349152cda31fa98fd1c711d6107393))
* Revert "Add Provision info to occ account:export command" ([260dc4e](https://github.com/nextcloud/mail/commit/260dc4e2f9e09740a483e092fa5ef897f4adc56b))
* Revert "Explicitly specify Postgres 14 as NC25 isn't compatible with Postgres 15" ([370c29e](https://github.com/nextcloud/mail/commit/370c29e27dc8c6f12aee4132df874f6792c20e85))



# [2.0.0-RC1](https://github.com/nextcloud/mail/compare/v2.0.0-beta.7...v2.0.0-RC1) (2022-09-26)



# [2.0.0-beta4](https://github.com/nextcloud/mail/compare/v2.0.0-beta3...v2.0.0-beta4) (2022-09-08)



# [2.0.0-beta3](https://github.com/nextcloud/mail/compare/v2.0.0-beta1...v2.0.0-beta3) (2022-09-06)



# [2.0.0-beta1](https://github.com/nextcloud/mail/compare/v1.14.0-beta1...v2.0.0-beta1) (2022-09-05)


### Reverts

* Revert "Update vue to 5.4.0" ([970bcc4](https://github.com/nextcloud/mail/commit/970bcc409e60f4abc6d0bf628cd5d60cd8995011))



# [1.13.0-beta3](https://github.com/nextcloud/mail/compare/v1.13.0-beta1...v1.13.0-beta3) (2022-05-23)


### Reverts

* Revert "Check account for currentness before reprovisioning it" ([27fe297](https://github.com/nextcloud/mail/commit/27fe2975aac226aad344d7a54b0858d136565d91))



# [1.12.0-rc.1](https://github.com/nextcloud/mail/compare/v1.11.0...v1.12.0-rc.1) (2022-04-14)


### Reverts

* Revert "Add more logging to IMAP to DB conversion" ([9ed5c3b](https://github.com/nextcloud/mail/commit/9ed5c3b2332137461d77da8b30cb66b02068a5ee))



# [1.11.0](https://github.com/nextcloud/mail/compare/v1.11.0-rc1...v1.11.0) (2021-11-29)



# [1.11.0-rc1](https://github.com/nextcloud/mail/compare/v1.10.0-RC.1...v1.11.0-rc1) (2021-11-18)


### Bug Fixes

* handle invalid imap message id ([b2cdc5a](https://github.com/nextcloud/mail/commit/b2cdc5a8ac1a061ed9521274d92a548074a0e1ca))



# [1.10.0-RC.1](https://github.com/nextcloud/mail/compare/v1.10.0-alpha.7...v1.10.0-RC.1) (2021-06-22)



# [1.10.0-alpha.7](https://github.com/nextcloud/mail/compare/v1.10.0-alpha.6...v1.10.0-alpha.7) (2021-06-10)



# [1.10.0-alpha.6](https://github.com/nextcloud/mail/compare/v1.10.0-alpha.4...v1.10.0-alpha.6) (2021-06-01)



# [1.9.0](https://github.com/nextcloud/mail/compare/v1.9.0-alpha3...v1.9.0) (2021-03-03)



# [1.9.0-alpha3](https://github.com/nextcloud/mail/compare/v1.9.0-alpha2...v1.9.0-alpha3) (2021-03-01)



# [1.8.0](https://github.com/nextcloud/mail/compare/v1.7.0...v1.8.0) (2021-01-20)



# [1.7.0](https://github.com/nextcloud/mail/compare/v1.6.0...v1.7.0) (2020-11-11)



# [1.6.0](https://github.com/nextcloud/mail/compare/v1.5.0...v1.6.0) (2020-11-04)


### Reverts

* Revert "Bump @ckeditor/ckeditor5-editor-balloon from 23.0.0 to 23.1.0" ([fb1235c](https://github.com/nextcloud/mail/commit/fb1235cba31b12519f6b884e2774067eefe2a332))
* Revert "Bump @ckeditor/ckeditor5-alignment from 23.0.0 to 23.1.0" ([f052364](https://github.com/nextcloud/mail/commit/f0523642f312727da08de1d5703992dd936868cf))
* Revert "Bump @ckeditor/ckeditor5-block-quote from 23.0.0 to 23.1.0" ([edb4035](https://github.com/nextcloud/mail/commit/edb403518a06ec1ae47b5be9e070584f42b545ce))
* Revert "Bump @ckeditor/ckeditor5-heading from 23.0.0 to 23.1.0" ([1a4c331](https://github.com/nextcloud/mail/commit/1a4c33165912d068d3003b3a6d859d9c8ae47682))



# [1.5.0](https://github.com/nextcloud/mail/compare/v1.5.0-rc2...v1.5.0) (2020-10-02)



# [1.5.0-rc2](https://github.com/nextcloud/mail/compare/v1.5.0-rc1...v1.5.0-rc2) (2020-10-02)



# [1.5.0-rc1](https://github.com/nextcloud/mail/compare/v1.5.0-beta3...v1.5.0-rc1) (2020-09-29)



# [1.5.0-beta2](https://github.com/nextcloud/mail/compare/v1.5.0-beta1...v1.5.0-beta2) (2020-09-22)



# [1.5.0-beta1](https://github.com/nextcloud/mail/compare/v1.5.0-alpha3...v1.5.0-beta1) (2020-09-22)



# [1.4.0-rc1](https://github.com/nextcloud/mail/compare/v1.4.0-beta2...v1.4.0-rc1) (2020-06-04)



# [1.4.0-beta2](https://github.com/nextcloud/mail/compare/v1.4.0-beta1...v1.4.0-beta2) (2020-05-26)



# [1.4.0-beta1](https://github.com/nextcloud/mail/compare/v1.3.3...v1.4.0-beta1) (2020-05-20)



## [1.3.3](https://github.com/nextcloud/mail/compare/v1.3.2...v1.3.3) (2020-04-21)



## [1.3.2](https://github.com/nextcloud/mail/compare/v1.3.1...v1.3.2) (2020-04-16)



## [1.3.1](https://github.com/nextcloud/mail/compare/v1.3.0...v1.3.1) (2020-04-16)



# [1.3.0-beta1](https://github.com/nextcloud/mail/compare/v1.1.2...v1.3.0-beta1) (2020-02-11)



## [1.1.2](https://github.com/nextcloud/mail/compare/v1.1.1...v1.1.2) (2020-01-30)



## [1.1.1](https://github.com/nextcloud/mail/compare/v1.1.0...v1.1.1) (2020-01-27)



# [1.1.0](https://github.com/nextcloud/mail/compare/v1.0.0...v1.1.0) (2020-01-27)



# [1.0.0](https://github.com/nextcloud/mail/compare/v0.21.1...v1.0.0) (2020-01-17)



## [0.21.1](https://github.com/nextcloud/mail/compare/v0.21.0...v0.21.1) (2020-01-07)



# [0.21.0](https://github.com/nextcloud/mail/compare/v0.20.3...v0.21.0) (2019-12-17)



## [0.20.3](https://github.com/nextcloud/mail/compare/v0.20.2...v0.20.3) (2019-12-16)



## [0.20.2](https://github.com/nextcloud/mail/compare/v0.20.1...v0.20.2) (2019-12-13)



## [0.20.1](https://github.com/nextcloud/mail/compare/v0.20.0...v0.20.1) (2019-12-09)



## [0.19.1](https://github.com/nextcloud/mail/compare/v0.19.0...v0.19.1) (2019-12-03)



# [0.19.0](https://github.com/nextcloud/mail/compare/v0.18.1...v0.19.0) (2019-11-25)



## [0.18.1](https://github.com/nextcloud/mail/compare/v0.18.0...v0.18.1) (2019-11-04)



# [0.18.0](https://github.com/nextcloud/mail/compare/v0.18.0-RC1...v0.18.0) (2019-10-28)


### Reverts

* Revert "Bump @ckeditor/ckeditor5-basic-styles from 11.1.4 to 15.0.0" ([7f97125](https://github.com/nextcloud/mail/commit/7f97125453a21685aafb344796fe36cbc907d73a))
* Revert "Bump @ckeditor/ckeditor5-build-balloon from 12.4.0 to 15.0.0" ([c8e4831](https://github.com/nextcloud/mail/commit/c8e48311daa8afab9ba7e43c14988e15a070fb4e))
* Revert "Bump @ckeditor/ckeditor5-essentials from 11.0.5 to 15.0.0" ([6a514cf](https://github.com/nextcloud/mail/commit/6a514cf2b731afbc59b3813ca0d72ead8ccad174))



# [0.17.0](https://github.com/nextcloud/mail/compare/v0.16.0...v0.17.0) (2019-09-02)



# [0.16.0](https://github.com/nextcloud/mail/compare/v0.15.5...v0.16.0) (2019-08-29)



## [0.15.5](https://github.com/nextcloud/mail/compare/v0.15.4...v0.15.5) (2019-08-28)



## [0.15.4](https://github.com/nextcloud/mail/compare/v0.15.3...v0.15.4) (2019-08-26)



## [0.15.3](https://github.com/nextcloud/mail/compare/v0.15.2...v0.15.3) (2019-08-26)



# [0.12.0-RC1](https://github.com/nextcloud/mail/compare/v0.12.0-beta5...v0.12.0-RC1) (2019-03-08)



# [0.12.0-RC2](https://github.com/nextcloud/mail/compare/v0.12.0-RC1...v0.12.0-RC2) (2019-03-27)



# [0.12.0-RC1](https://github.com/nextcloud/mail/compare/v0.12.0-beta5...v0.12.0-RC1) (2019-03-08)



# [0.12.0-beta4](https://github.com/nextcloud/mail/compare/v0.12.0-beta2...v0.12.0-beta4) (2019-03-01)


### Reverts

* Revert "Remove lock bot config temporarily" ([57b9771](https://github.com/nextcloud/mail/commit/57b977175dde6fde71fd06e38ddd29b2470b2b6c))



# [0.12.0-alpha3](https://github.com/nextcloud/mail/compare/v0.12.0-alpha2...v0.12.0-alpha3) (2018-11-13)



# [0.12.0-alpha3](https://github.com/nextcloud/mail/compare/v0.12.0-alpha2...v0.12.0-alpha3) (2018-11-13)



# [0.11.0-beta1](https://github.com/nextcloud/mail/compare/v0.10.0...v0.11.0-beta1) (2018-10-16)



# [0.10.0-rc1](https://github.com/nextcloud/mail/compare/v0.10.0-beta1...v0.10.0-rc1) (2018-08-21)



# [0.10.0-beta1](https://github.com/nextcloud/mail/compare/v0.9.0...v0.10.0-beta1) (2018-08-20)



# [0.9.0-rc1](https://github.com/nextcloud/mail/compare/v0.9.0-beta3...v0.9.0-rc1) (2018-08-08)



# [0.9.0-beta1](https://github.com/nextcloud/mail/compare/v0.8.3...v0.9.0-beta1) (2018-08-03)



## [0.8.3-alpha1](https://github.com/nextcloud/mail/compare/v0.8.2...v0.8.3-alpha1) (2018-07-24)



## [0.8.2-alpha3](https://github.com/nextcloud/mail/compare/v0.8.2-alpha2...v0.8.2-alpha3) (2018-06-26)



## [0.8.2-alpha2](https://github.com/nextcloud/mail/compare/v0.8.1...v0.8.2-alpha2) (2018-06-26)



# [0.8.0-alpha1](https://github.com/nextcloud/mail/compare/v0.7.10...v0.8.0-alpha1) (2018-04-03)



## [0.7.8](https://github.com/nextcloud/mail/compare/v0.7.7...v0.7.8) (2018-01-15)



## [0.7.4](https://github.com/nextcloud/mail/compare/v0.7.3...v0.7.4) (2017-10-30)



## [0.7.2](https://github.com/nextcloud/mail/compare/v0.7.1...v0.7.2) (2017-09-06)



# [0.2.0](https://github.com/nextcloud/mail/compare/v0.1.3...v0.2.0) (2015-08-28)



# [0.1.0](https://github.com/nextcloud/mail/compare/3098050b22d5ae619cf27772981fcc2a5e7960d0...v0.1.0) (2015-05-11)


### Reverts

* Revert "getStatus()->utf8/utf7imap does not always return with INBOX prefix, let's clean it up for good" ([203ddf5](https://github.com/nextcloud/mail/commit/203ddf5e8da689a2bfdffa8700c13bf444998d14))
* Revert "Remove wrapping div from backbone view for folders list" ([3098050](https://github.com/nextcloud/mail/commit/3098050b22d5ae619cf27772981fcc2a5e7960d0))




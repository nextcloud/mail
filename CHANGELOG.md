## [5.5.13](https://github.com/nextcloud/mail/compare/v5.5.12...v5.5.13) (2025-11-05)


### Bug Fixes

* **db:** drop index on mail_mailboxes by columns instead of name ([86a693f](https://github.com/nextcloud/mail/commit/86a693f3c49a22d83a7146882df3eb9529201127))
* **l10n:** Update translations from Transifex ([0303882](https://github.com/nextcloud/mail/commit/03038829fce0054e521cac0e83fde602d86ad7ff))
* **NewMessageModal:** keep formatting after minimizing ([9d11a84](https://github.com/nextcloud/mail/commit/9d11a84882744d24e227f43eb2dcc4c3368837cf))



## [5.5.12](https://github.com/nextcloud/mail/compare/v5.5.11...v5.5.12) (2025-11-04)


### Bug Fixes

* **dns:** Update public suffix list ([3c65f16](https://github.com/nextcloud/mail/commit/3c65f1612fd29a970bb91e9a0a1ff819acf43bd5))
* **l10n:** Update translations from Transifex ([87d0c0e](https://github.com/nextcloud/mail/commit/87d0c0e8f73b592f8326be4b02843a5fb300e5fe))
* **l10n:** Update translations from Transifex ([946dada](https://github.com/nextcloud/mail/commit/946dada6a23ec301fc00355fa30c8da746739e35))
* **l10n:** Update translations from Transifex ([7a86d2d](https://github.com/nextcloud/mail/commit/7a86d2d7a61d8b778b0bb98bc9122587c123a716))
* **l10n:** Update translations from Transifex ([9d41284](https://github.com/nextcloud/mail/commit/9d412843432b851e4cb8861415a5e25285e9cbf9))
* **l10n:** Update translations from Transifex ([4c80b1b](https://github.com/nextcloud/mail/commit/4c80b1bdd0ea8a9be9f2c455bed4a78e97354a3f))
* **l10n:** Update translations from Transifex ([1bad43a](https://github.com/nextcloud/mail/commit/1bad43a58235f3cf7b6b3b46a47d3adcbe56b268))
* **l10n:** Update translations from Transifex ([41b17c4](https://github.com/nextcloud/mail/commit/41b17c4da4fbb71404a073981e14a0fd93bcd90f))
* **l10n:** Update translations from Transifex ([5dcb047](https://github.com/nextcloud/mail/commit/5dcb047c5ecf251c1f1b3a4773fc75d0211a9dac))
* **l10n:** Update translations from Transifex ([9370677](https://github.com/nextcloud/mail/commit/937067734772c53923bab7c6260f49cfeec57da1))
* **l10n:** Update translations from Transifex ([dd9f454](https://github.com/nextcloud/mail/commit/dd9f45459213e9268b39b582d3f9a1ee6563bce9))



## [5.5.11](https://github.com/nextcloud/mail/compare/v5.5.10...v5.5.11) (2025-10-22)


### Bug Fixes

* also await onToggleJunk ([b1c9511](https://github.com/nextcloud/mail/commit/b1c95116d43c2387277b0a98734e61936dd46296))
* **l10n:** Update translations from Transifex ([923d4da](https://github.com/nextcloud/mail/commit/923d4da5d5f50cac359ca868fbae379031aeb29b))
* **l10n:** Update translations from Transifex ([0458af6](https://github.com/nextcloud/mail/commit/0458af61360eefe3b4f299c411f3384c27b053cf))
* **l10n:** Update translations from Transifex ([f0c7aab](https://github.com/nextcloud/mail/commit/f0c7aabb1a66dea3aa4208d870a48f4ddb9bc104))
* **l10n:** Update translations from Transifex ([73f521e](https://github.com/nextcloud/mail/commit/73f521e08f01ffacab8a9b3a2b37639a1d711917))
* **l10n:** Update translations from Transifex ([a7fc2fe](https://github.com/nextcloud/mail/commit/a7fc2fe52af18e939fdcbfba6c1f232ac89f70e6))
* **quick-actions:** apply action to all messages in a thread ([777da08](https://github.com/nextcloud/mail/commit/777da08d7c13ee90550dc3d8a0b692133a6a5d70))
* **quick-actions:** delete local steps ([40cc7ac](https://github.com/nextcloud/mail/commit/40cc7ac41786674f84c624b1fd9427fc2bcb124e))
* **setup:** log when invalid hosts are used ([9def37a](https://github.com/nextcloud/mail/commit/9def37a0bdc5465aef39c39ef6ec0e507b84b954))



## [5.5.10](https://github.com/nextcloud/mail/compare/v5.5.9...v5.5.10) (2025-10-16)


### Bug Fixes

* **db:** clean-up old mailboxes account_id+name index ([467a76b](https://github.com/nextcloud/mail/commit/467a76bcd902c081c2db2c5d317b0b9000a3115b))
* **deps:** Fix npm audit ([574eb45](https://github.com/nextcloud/mail/commit/574eb458db191b7ea05c95770724a9af995a5e3c))
* **dns:** Update public suffix list ([b25e839](https://github.com/nextcloud/mail/commit/b25e8390d99748a3a345acdfaf97900eece319a7))
* **l10n:** Update translations from Transifex ([8b4a8a7](https://github.com/nextcloud/mail/commit/8b4a8a725208c2a4b2a973c7eb49ad52167aaf2b))
* **l10n:** Update translations from Transifex ([e273858](https://github.com/nextcloud/mail/commit/e2738589c13d751e659016211d73aad3b26ea75e))
* list separators depending on sort by date ([df29758](https://github.com/nextcloud/mail/commit/df29758718cc9259a55c62e24eb16c12d653736b))



## [5.5.9](https://github.com/nextcloud/mail/compare/v5.5.8...v5.5.9) (2025-10-14)


### Bug Fixes

* **ai-integration:** get user language for message summary ([9e0721f](https://github.com/nextcloud/mail/commit/9e0721f711ee39a0cf00929b2cae1466e8d8d14b))
* **deps:** bump phpmailer/dkimvalidator from 0.3 to ^0.3.1 ([1440f7a](https://github.com/nextcloud/mail/commit/1440f7a60f323de7416855e7fe56a00765bf6d5b))
* imip aliases and capitalization ([1a14acd](https://github.com/nextcloud/mail/commit/1a14acd95117cfe195e6516795de7766b1a5a601))
* **l10n:** Update translations from Transifex ([70c7320](https://github.com/nextcloud/mail/commit/70c7320e0561ac575125be958b35f8fd208d7c19))



## [5.5.8](https://github.com/nextcloud/mail/compare/v5.5.7...v5.5.8) (2025-10-13)


### Bug Fixes

* allow storing longer mailbox names ([095dc16](https://github.com/nextcloud/mail/commit/095dc16c12be4834fae515c6cee3efcae3f6097a))
* **deps:** update axios ([3d78ef7](https://github.com/nextcloud/mail/commit/3d78ef75699c9e3ea1347af141050975740c81dd))
* **l10n:** Update translations from Transifex ([6e94ce5](https://github.com/nextcloud/mail/commit/6e94ce502d5f088537e61f094b7e2793e01c187a))
* **l10n:** Update translations from Transifex ([c0d94a8](https://github.com/nextcloud/mail/commit/c0d94a8a196b7d851a123db84906a95b48f21bec))
* **l10n:** Update translations from Transifex ([8e4f87d](https://github.com/nextcloud/mail/commit/8e4f87d00a7443e5b007cdff83ae4394b72622e4))
* **l10n:** Update translations from Transifex ([5d83889](https://github.com/nextcloud/mail/commit/5d8388974291f337d0aa94035c56f2ca45df3d9c))
* **l10n:** Update translations from Transifex ([5963b59](https://github.com/nextcloud/mail/commit/5963b593eccb0cc29fd6a12a1cc05c6564442ea5))
* render recipient info later ([1756f26](https://github.com/nextcloud/mail/commit/1756f26e2d2b51125583f0b5ef2828cb5482bc5b))



## [5.5.7](https://github.com/nextcloud/mail/compare/v5.5.6...v5.5.7) (2025-10-07)


### Bug Fixes

* **dns:** Update public suffix list ([928ca34](https://github.com/nextcloud/mail/commit/928ca3402abcef567a5ba8c5f7e2c124d6e34480))
* **l10n:** Update translations from Transifex ([ece8ffc](https://github.com/nextcloud/mail/commit/ece8ffcf81d472647cc103dd51e1e4cfb41efa57))
* **l10n:** Update translations from Transifex ([3e87978](https://github.com/nextcloud/mail/commit/3e879781edbef305cf5fe6042b4dc7e7ac72936d))
* **l10n:** Update translations from Transifex ([a7fd4cf](https://github.com/nextcloud/mail/commit/a7fd4cf4fd3bfe486a7b06908654714c7eaf357b))
* leftover design changes ([936dee6](https://github.com/nextcloud/mail/commit/936dee64119875051a8f44d4a4ec488cf3ac66bb))
* migrate mail_accounts.oauth_refresh_token to Types::TEXT ([3f1dc90](https://github.com/nextcloud/mail/commit/3f1dc908919b34b612b6bd5ff6674a7ab7cee787))
* **ui:** use new NcKbd component ([7504250](https://github.com/nextcloud/mail/commit/75042509ac830e42ad138bd3bd1162deaefafc0e))



## [5.5.6](https://github.com/nextcloud/mail/compare/v5.5.5...v5.5.6) (2025-09-29)


### Bug Fixes

* **l10n:** Update translations from Transifex ([d4780e8](https://github.com/nextcloud/mail/commit/d4780e8de4585aeb4565384555f2b36ce7cb2efd))
* **l10n:** Update translations from Transifex ([abb458c](https://github.com/nextcloud/mail/commit/abb458c7d5166a08c87773540b0c00994bad3720))
* mail pane resetting between layout changes ([92a6b3d](https://github.com/nextcloud/mail/commit/92a6b3dc648477bdfde5033d3e481f6b6235e504))
* **ui:** use filled icons for active navigation mailboxes ([fa8546d](https://github.com/nextcloud/mail/commit/fa8546d53a45ba003f39b634db4cba08fef2cb2f))



## [5.5.5](https://github.com/nextcloud/mail/compare/v5.5.4...v5.5.5) (2025-09-26)


### Bug Fixes

* allow reloading the INBOX favorites page ([f1b608d](https://github.com/nextcloud/mail/commit/f1b608d1bdae85f98d85b3ecc451879c2096532b))
* display tooltip correctly ([266f33f](https://github.com/nextcloud/mail/commit/266f33f617fb5f93c974ba18cf36a224312b69ea))
* **l10n:** Update translations from Transifex ([265568d](https://github.com/nextcloud/mail/commit/265568df6aba02a57e0b47bb8ef6a6d8536d47a4))
* **migration:** guard repair stop for possibly missing method ([16ce02c](https://github.com/nextcloud/mail/commit/16ce02c31790a04b50e7db3dc5b474ca80a536d6))
* validate email address in recipient picker ([7756055](https://github.com/nextcloud/mail/commit/775605548f91086b2e5db433b10e60f49187e927))



## [5.5.4](https://github.com/nextcloud/mail/compare/v5.5.3...v5.5.4) (2025-09-25)


### Bug Fixes

* **migration:** check if account service method exists ([c58f9a4](https://github.com/nextcloud/mail/commit/c58f9a48a53508effe2c6900fd2b8753a7f638b1))



## [5.5.3](https://github.com/nextcloud/mail/compare/v5.5.2...v5.5.3) (2025-09-25)


### Bug Fixes

* **deps:** Fix npm audit ([c1dbf23](https://github.com/nextcloud/mail/commit/c1dbf233d91d81ebc5282838a65cd7658357b493))
* flip email open and email icon in evelope actions ([98f6993](https://github.com/nextcloud/mail/commit/98f6993dc2c8a8068f6b445b35346f2c7c69dd2d))
* **l10n:** Update translations from Transifex ([539ec83](https://github.com/nextcloud/mail/commit/539ec83bd22f724ea598fd6c810ef892e3c9bb30))
* **l10n:** Update translations from Transifex ([1069e7c](https://github.com/nextcloud/mail/commit/1069e7ca421b341c8dfdf061ce4230b352664768))
* schedule jobs on account provisioning ([5ec7a12](https://github.com/nextcloud/mail/commit/5ec7a121847c2a110f94d929508caa143c5011a5))
* schedule repair sync job when adding an account ([60536a0](https://github.com/nextcloud/mail/commit/60536a0ffe838c36e9127339e5962a41c89ba148))
* **ui:** escape envelope subject line ([8339f91](https://github.com/nextcloud/mail/commit/8339f9110c4c6eb50bb1d56d2f2c00da90ed852b))
* **ui:** handle mailbox sorting of 'all' and unknown special use ([1a8ba2c](https://github.com/nextcloud/mail/commit/1a8ba2ca6340cc74b6cf78aaa3061a77cdf818a4))



## [5.5.2](https://github.com/nextcloud/mail/compare/v5.5.1...v5.5.2) (2025-09-23)


### Bug Fixes

* align quick action settings button properly ([895c2a1](https://github.com/nextcloud/mail/commit/895c2a1337b8fe6b3dcfb071d549c6c8968acc46))
* check email in recipient picker ([43ae8a6](https://github.com/nextcloud/mail/commit/43ae8a6725bb5d2468cd1de7c7fe253c4ffa0870))
* ckeditor color in darkmode ([39eb2de](https://github.com/nextcloud/mail/commit/39eb2dec88135d972d96c80ff0779331395c6a85))
* contrast fixes for some elements ([5ec43ce](https://github.com/nextcloud/mail/commit/5ec43ce11059f4d032fa1b4035165d92081b117a))
* **EnvelopeSkeleton:** improve accessibility of actions on mobile by disabling browser context menu overlapping our actions ([22ab629](https://github.com/nextcloud/mail/commit/22ab6293f047a90933530a712669ab35e2b2a6a9))
* error in mounted hook ([f76c3a2](https://github.com/nextcloud/mail/commit/f76c3a2b6aeba3bf5c177c1cf0892ef414a9f946))
* **l10n:** Update translations from Transifex ([8716055](https://github.com/nextcloud/mail/commit/871605574f60fd94814f21ff0e18716491761dc9))
* **l10n:** Update translations from Transifex ([880ce10](https://github.com/nextcloud/mail/commit/880ce10b872a9bc7718d3abc20c484027d18fe10))
* **l10n:** Update translations from Transifex ([4ced027](https://github.com/nextcloud/mail/commit/4ced027bc8f1300171c355392f8479728d2cc650))
* **l10n:** Update translations from Transifex ([ba5ee6d](https://github.com/nextcloud/mail/commit/ba5ee6d73c9ebc8c7df0021cc0a8398e2239919e))
* **l10n:** Update translations from Transifex ([ff46caa](https://github.com/nextcloud/mail/commit/ff46caa34fb3681507e1fdfcf38d9d90600201f5))
* **l10n:** Update translations from Transifex ([d9266d9](https://github.com/nextcloud/mail/commit/d9266d9d28aeab668e949a2e132ceb5697f1025a))
* **quickaction:** fix deleting quick action steps ([7963895](https://github.com/nextcloud/mail/commit/7963895f8809e5a64b24b49eb9edfb9e3a3c5777))
* **quickaction:** log js error to console ([5c26e43](https://github.com/nextcloud/mail/commit/5c26e4306c02ed409c687955704ceeea901881a9))
* restore color menu in text/background selector ([3929b7d](https://github.com/nextcloud/mail/commit/3929b7d63ba11cdb31edfac87bd0c782a4a66f70))
* toolbar dropdown and height bug ([36dc23e](https://github.com/nextcloud/mail/commit/36dc23efd96a4231937a2dc5332a0a6cc40ebfe4))
* **ui:** Don't outline checkmark icons ([024108f](https://github.com/nextcloud/mail/commit/024108f0ee25835342ab13714384fe3e8c815cc3)), closes [#11322](https://github.com/nextcloud/mail/issues/11322)



## [5.5.1](https://github.com/nextcloud/mail/compare/v5.5.0...v5.5.1) (2025-09-17)


### Bug Fixes

* **classification:** break large SQL IN queries into chunks ([ac201f0](https://github.com/nextcloud/mail/commit/ac201f0ea879ff69e4b0e944998e137bf7202910))



# [5.5.0](https://github.com/nextcloud/mail/compare/v5.5.0-rc.2...v5.5.0) (2025-09-17)


### Bug Fixes

* **deps:** bump @nextcloud/vue to ^8.31.0 ([7c0db73](https://github.com/nextcloud/mail/commit/7c0db7317ea167152c761ab109b373e54b659b70))
* **l10n:** Update translations from Transifex ([19affff](https://github.com/nextcloud/mail/commit/19afffff7c650a1f79f626aa6eef1112266fe428))
* **sieve:** improve filter ui ([ec7f414](https://github.com/nextcloud/mail/commit/ec7f41400ab2f2fc162a40643d7363de69f8cf11))



# [5.5.0-rc.2](https://github.com/nextcloud/mail/compare/v5.5.0-rc.1...v5.5.0-rc.2) (2025-09-16)


### Bug Fixes

* correct the default sort order value ([94ce080](https://github.com/nextcloud/mail/commit/94ce0800ff3aa2c6cd38dd260d89981c9b0bc35c))
* **dns:** Update public suffix list ([c25c602](https://github.com/nextcloud/mail/commit/c25c60242c41ee5f505189d9f76fb3bfe4e792f1))
* external address color ([e05a397](https://github.com/nextcloud/mail/commit/e05a39780c1488c0d638f13a62eb1a81c0bdade6))
* **l10n:** Update translations from Transifex ([512e454](https://github.com/nextcloud/mail/commit/512e45468d51dd24f6b668e3d79a3b542e95cd5d))
* **l10n:** Update translations from Transifex ([0d0a5d5](https://github.com/nextcloud/mail/commit/0d0a5d57e6b906ae130a5216ab673b218c4932c5))
* **l10n:** Update translations from Transifex ([d1f927e](https://github.com/nextcloud/mail/commit/d1f927e78e40708403cdecc1d462eb7614963b4d))
* **l10n:** Update translations from Transifex ([43a21d7](https://github.com/nextcloud/mail/commit/43a21d72a4a0f5ed15727ca851c7685deacf0bf5))
* **l10n:** Update translations from Transifex ([bf9ed44](https://github.com/nextcloud/mail/commit/bf9ed446635925ce8b7e3737143ddc0e37e4f8d3))
* show user avatar in recipient info ([9b175bd](https://github.com/nextcloud/mail/commit/9b175bd480a9e6d3829291d489314c31fc55e73d))



# [5.5.0-rc.1](https://github.com/nextcloud/mail/compare/v5.2.0-beta.1...v5.5.0-rc.1) (2025-09-11)


### Bug Fixes

* **ai-integration:** set the language explicitly for message summary ([ca141ee](https://github.com/nextcloud/mail/commit/ca141eeed5f90a5b1348c2f7c3d6b24680557edb))
* **AliasSettings:** alignment ([84233ab](https://github.com/nextcloud/mail/commit/84233ab11532efd238c876f92d3f5b60e3faabad))
* avoid polluting the logs in the task processing event listner ([ab9cd93](https://github.com/nextcloud/mail/commit/ab9cd9338b2de6bf6c2daee9d291c9c995ed7517))
* ckeditor dropdown overlaping ([59904c2](https://github.com/nextcloud/mail/commit/59904c2c6b2d3476a4e3bb2cfdb23cdb44809bd3))
* clear search icon should not be outline ([ab21936](https://github.com/nextcloud/mail/commit/ab2193695614d46132416effbe41b4bb5921896d))
* **deps:** bump @iframe-resizer/child from 5.5.0 to ^5.5.1 (main) ([#11451](https://github.com/nextcloud/mail/issues/11451)) ([fbb7d7b](https://github.com/nextcloud/mail/commit/fbb7d7b61d0c666f984ec81de2da11139e1f211e))
* **deps:** bump @iframe-resizer/child from 5.5.1 to ^5.5.2 ([ed1fbcb](https://github.com/nextcloud/mail/commit/ed1fbcb085d326fdcecdeb792f6833ca2b325967))
* **deps:** bump @iframe-resizer/child from 5.5.2 to ^5.5.3 (main) ([#11583](https://github.com/nextcloud/mail/issues/11583)) ([03dedb7](https://github.com/nextcloud/mail/commit/03dedb7baf7205d8aac8429d05b0200f2672a5c0))
* **deps:** bump @iframe-resizer/child from 5.5.3 to ^5.5.5 (main) ([#11612](https://github.com/nextcloud/mail/issues/11612)) ([0625a6f](https://github.com/nextcloud/mail/commit/0625a6f9d4216454dc5aaf9276e3091085920f40))
* **deps:** bump @iframe-resizer/parent from 5.4.7 to ^5.5.1 (main) ([#11452](https://github.com/nextcloud/mail/issues/11452)) ([e7dd17a](https://github.com/nextcloud/mail/commit/e7dd17add842d88f5a05e2e1fcaaa92275208f07))
* **deps:** bump @iframe-resizer/parent from 5.5.1 to ^5.5.2 (main) ([#11483](https://github.com/nextcloud/mail/issues/11483)) ([c563201](https://github.com/nextcloud/mail/commit/c563201340e02673d364e10efeef4bacc3050624))
* **deps:** bump @iframe-resizer/parent from 5.5.2 to ^5.5.3 (main) ([#11584](https://github.com/nextcloud/mail/issues/11584)) ([67174a9](https://github.com/nextcloud/mail/commit/67174a9f80d80b8cf3cc2f28fbe5d72da11b541b))
* **deps:** bump @iframe-resizer/parent from 5.5.3 to ^5.5.5 (main) ([#11613](https://github.com/nextcloud/mail/issues/11613)) ([5be27cf](https://github.com/nextcloud/mail/commit/5be27cf2cf00b72fcd4526f164ec2c773e46e00c))
* **deps:** bump @nextcloud/files from 3.10.2 to ^3.12.0 ([deef58a](https://github.com/nextcloud/mail/commit/deef58a71e8801dc0c5e515295d03686ece51ff3))
* **deps:** bump @nextcloud/initial-state from 2.2.0 to v3 ([4529411](https://github.com/nextcloud/mail/commit/452941193bd47e1deb5da403b9ef06ac12d05c25))
* **deps:** bump @nextcloud/vue from 8.27.0 to ^8.28.0 ([88bdf89](https://github.com/nextcloud/mail/commit/88bdf891061228035dea3ddfe6d360a79a8cca33))
* **deps:** bump @nextcloud/vue to ^8.30.0 ([f417737](https://github.com/nextcloud/mail/commit/f4177370703e0f9589ca11e89c72535a7782ee8b))
* **deps:** bump core-js from 3.43.0 to ^3.45.0 ([4932f23](https://github.com/nextcloud/mail/commit/4932f23e4d0fc76c74b602a7cd7e631e77d7dab6))
* **deps:** bump ical.js from 2.2.0 to ^2.2.1 ([13f5e61](https://github.com/nextcloud/mail/commit/13f5e61ef534906b8c7dc1ebb5cd36c1c9b85a24))
* **deps:** bump js-base64 from 3.7.7 to ^3.7.8 (main) ([#11501](https://github.com/nextcloud/mail/issues/11501)) ([ded3bda](https://github.com/nextcloud/mail/commit/ded3bda4d340bffd12adcdd52b6253b3c9170431))
* **deps:** bump linkifyjs to v4.3.2 ([ec06a65](https://github.com/nextcloud/mail/commit/ec06a658d8fc89369dabedb2e70dc1818f48c245))
* **deps:** bump p-limit from 5.0.0 to v6 ([4d6501a](https://github.com/nextcloud/mail/commit/4d6501ac9952a57bf9a38b47f497113d7af7e008))
* **deps:** bump sabberworm/php-css-parser from 8.8.0 to ^8.9.0 (main) ([#11475](https://github.com/nextcloud/mail/issues/11475)) ([a96c15d](https://github.com/nextcloud/mail/commit/a96c15d684506a68817817f0e0c3ce6af567ec2c))
* **deps:** bump sha.js from 2.4.11 to 2.4.12 ([461a9cf](https://github.com/nextcloud/mail/commit/461a9cfedcd62a89d236ae86421d9c3ffd519a18))
* **deps:** bump stylelint from 16.20.0 to ^16.23.1 (main) ([#11476](https://github.com/nextcloud/mail/issues/11476)) ([eac87aa](https://github.com/nextcloud/mail/commit/eac87aa75304bd0ecfda0425c84ea27477d83bf3))
* **deps:** bump uuid from 9.0.1 to v11 ([243172c](https://github.com/nextcloud/mail/commit/243172c9c8ebaaa2e0feb3907aa4dbe21842f133))
* **deps:** bump youthweb/urllinker from 2.0.0 to ^2.1.0 (main) ([#11477](https://github.com/nextcloud/mail/issues/11477)) ([e4cc571](https://github.com/nextcloud/mail/commit/e4cc571bef4399dbc1ec66c4238857b087db08c1))
* **deps:** update symfony/process to 5.4.46 ([569e6b9](https://github.com/nextcloud/mail/commit/569e6b9c5ce88c2874e969d9de55e176e0043b67))
* **dns:** Update public suffix list ([c29ef10](https://github.com/nextcloud/mail/commit/c29ef10955207b12213bd3781bcea2870d1f51a4))
* **dns:** Update public suffix list ([e9c9068](https://github.com/nextcloud/mail/commit/e9c90681350a547bb750f29b2f17da9d81f83e53))
* envelope max height ([2e88454](https://github.com/nextcloud/mail/commit/2e88454c46fae764a42e8f43779f4d48241c52b5))
* **Envelope:** fix oneline mode envelopes jumping when hovering avatars ([#11641](https://github.com/nextcloud/mail/issues/11641)) ([79e09a1](https://github.com/nextcloud/mail/commit/79e09a1d301ea3e762f9608aa62a30dec7d1cd6a))
* **EnvelopeSkeleton:** make action hover area square again ([13cc096](https://github.com/nextcloud/mail/commit/13cc096a30ad19fb5a5aed1a23dca4a774f15d36))
* imip cancelled icon should be filled ([222dad1](https://github.com/nextcloud/mail/commit/222dad14b866e40f8ad548a4784f7b7733b860c8))
* improve the height and width of threads ([217f086](https://github.com/nextcloud/mail/commit/217f08620eaff42685933654bbcbbeadd94a1b0c))
* **l10n:** Update translations from Transifex ([8cbc64b](https://github.com/nextcloud/mail/commit/8cbc64be123186ba8271c5684df1f685bb7f451d))
* **l10n:** Update translations from Transifex ([14d2b10](https://github.com/nextcloud/mail/commit/14d2b105c7fce21368db46f58c93c68be349fd8c))
* **l10n:** Update translations from Transifex ([d58c2b5](https://github.com/nextcloud/mail/commit/d58c2b5fcbad02d7becc6466ef21464e858ab5e4))
* **l10n:** Update translations from Transifex ([3a3151a](https://github.com/nextcloud/mail/commit/3a3151af5f54767d7355a950c74477c8a46a63ce))
* **l10n:** Update translations from Transifex ([d3fe84d](https://github.com/nextcloud/mail/commit/d3fe84d53f68598a93d2b44183d6233452e1a6dd))
* **l10n:** Update translations from Transifex ([9290530](https://github.com/nextcloud/mail/commit/9290530f5f29e3a4a021ec91645a10042199b4f3))
* **l10n:** Update translations from Transifex ([c9f26dc](https://github.com/nextcloud/mail/commit/c9f26dc55a3a6740fdb0daf2d2d18182ebcdcda6))
* **l10n:** Update translations from Transifex ([90a0fd5](https://github.com/nextcloud/mail/commit/90a0fd51400150b678303c39a68dd4d40473f00b))
* **l10n:** Update translations from Transifex ([36360a3](https://github.com/nextcloud/mail/commit/36360a3f71c96a209a4355dabb03b1380a7611c3))
* **l10n:** Update translations from Transifex ([a102a6e](https://github.com/nextcloud/mail/commit/a102a6e36687e4e030a3848c044dc7c255de74e9))
* **l10n:** Update translations from Transifex ([c5116a7](https://github.com/nextcloud/mail/commit/c5116a7d6e9214cc9b2ace7cd583388b16ef0731))
* **l10n:** Update translations from Transifex ([b076b44](https://github.com/nextcloud/mail/commit/b076b4422b297d8c77249107320c8ef8a6b4547c))
* **l10n:** Update translations from Transifex ([61df478](https://github.com/nextcloud/mail/commit/61df47864d09f0f3124e56b3e2f0241c84de83b6))
* **l10n:** Update translations from Transifex ([7ca9af7](https://github.com/nextcloud/mail/commit/7ca9af720d5d837c9a8b29dc55b10dfd89bc9c4d))
* **l10n:** Update translations from Transifex ([f9e8db7](https://github.com/nextcloud/mail/commit/f9e8db7ea23ff350e2a5cffdb49adbe6f2932293))
* **l10n:** Update translations from Transifex ([12ed5be](https://github.com/nextcloud/mail/commit/12ed5be776b49a895399ce8a3bdd52733787217c))
* **l10n:** Update translations from Transifex ([af95f12](https://github.com/nextcloud/mail/commit/af95f12cb147df5fd83e783fb3949684d4d9e314))
* **l10n:** Update translations from Transifex ([607c73b](https://github.com/nextcloud/mail/commit/607c73ba81bf7c79de107fa56ac2bd861d9c1db0))
* **l10n:** Update translations from Transifex ([a61e1b9](https://github.com/nextcloud/mail/commit/a61e1b93d8a4a085db1616d4a9cb14c407369f12))
* **l10n:** Update translations from Transifex ([ee5e864](https://github.com/nextcloud/mail/commit/ee5e864bdf16649e594f74c0bb9bc114e466a1d0))
* **l10n:** Update translations from Transifex ([70a04cd](https://github.com/nextcloud/mail/commit/70a04cd8aa2a23b56a655b94af8b34ccc0148b8e))
* **l10n:** Update translations from Transifex ([7f023a0](https://github.com/nextcloud/mail/commit/7f023a06dc0db1fe403aa64a2ceb57d9b17147b9))
* **l10n:** Update translations from Transifex ([e5e9ec8](https://github.com/nextcloud/mail/commit/e5e9ec8764bd26732f1ea6ad91b4e165011c25ac))
* **l10n:** Update translations from Transifex ([3f9e474](https://github.com/nextcloud/mail/commit/3f9e474486e90d6e48d3e40b70a4c72611ea2dcf))
* **l10n:** Update translations from Transifex ([3356ea2](https://github.com/nextcloud/mail/commit/3356ea2ebd5ee1ba344b60dfa5f40ae607e7ba19))
* **l10n:** Update translations from Transifex ([eeced61](https://github.com/nextcloud/mail/commit/eeced61bcf4d4eaec87924f94c8160acafd0682a))
* **l10n:** Update translations from Transifex ([2ad3966](https://github.com/nextcloud/mail/commit/2ad3966c3ea5606436387b67da1d4807eb47587a))
* **l10n:** Update translations from Transifex ([cfdcf75](https://github.com/nextcloud/mail/commit/cfdcf757f2fe7a6936569d3c861bba1b1004fe05))
* **l10n:** Update translations from Transifex ([697c69b](https://github.com/nextcloud/mail/commit/697c69b5841c01a25b8cf7b0e4fed67d3443188f))
* **l10n:** Update translations from Transifex ([2f1c003](https://github.com/nextcloud/mail/commit/2f1c003418e40b7edad25f1f498e43e2b773a7d1))
* **l10n:** Update translations from Transifex ([36f0e7f](https://github.com/nextcloud/mail/commit/36f0e7fd38568d944000736136c94bb1b7a261a3))
* **l10n:** Update translations from Transifex ([e2c468f](https://github.com/nextcloud/mail/commit/e2c468fac3115bdd6d27c16b9009377421416c74))
* **l10n:** Update translations from Transifex ([c8f646c](https://github.com/nextcloud/mail/commit/c8f646c09d5d0c405babdefa026cf6a5ac2b1d2f))
* **l10n:** Update translations from Transifex ([3f1a40c](https://github.com/nextcloud/mail/commit/3f1a40c9b23ee7f9ba9ac3610cb877d8d8a59800))
* **l10n:** Update translations from Transifex ([481be86](https://github.com/nextcloud/mail/commit/481be86a5abe74d2b6e11ee7823e1ff9997b7d4f))
* Overwrite php-stemmer dependency from RubixML for PHP 8.4 compatibility ([cb94091](https://github.com/nextcloud/mail/commit/cb94091f38006b5bb1255edea7b5d836a5f647ba))
* reply buttons spacing ([989f540](https://github.com/nextcloud/mail/commit/989f540d22e398bce6b58c467bda93932b5f06db))
* **settings:** add missing mail settings heading ([a94cd4e](https://github.com/nextcloud/mail/commit/a94cd4e30cf2800d1e695dc805c549b6f1fae300))
* specify which mailbox to load more from ([e196775](https://github.com/nextcloud/mail/commit/e1967756d49467085fbad138f7c67c0881b10146))
* **text-blocks:** allow editing in composer and open api for non admin users ([d93d974](https://github.com/nextcloud/mail/commit/d93d974bf8642e80792402c23546391e2985561d))
* thread heading alignment and participants ([ea01e40](https://github.com/nextcloud/mail/commit/ea01e402f79b90c0792771e03980abec73165781))
* thread scrolls to middle of iframe ([77a91ad](https://github.com/nextcloud/mail/commit/77a91ad7765a9ec344c5987a8efe1834c9101343))
* **translation:** respect llm admin setting ([0b0e279](https://github.com/nextcloud/mail/commit/0b0e27977a69a6315fca731f65f554ee758e2e9b))
* **ui:** adapt setup page to fluid logo ([bff428d](https://github.com/nextcloud/mail/commit/bff428d7c78502008d296e232d998b0778eca37b))
* **ui:** replace download/upload icons with tray icons ([fe6c15f](https://github.com/nextcloud/mail/commit/fe6c15fe67478664e0021707a0e450d4d3d7e75b))
* **ui:** replace wrongly sized and jumping checkbox loading icon ([668f7b6](https://github.com/nextcloud/mail/commit/668f7b6c4b3017f0a65f486435d4ca81327d1399))
* **ui:** resize action icons from 16px to 20px ([d6810da](https://github.com/nextcloud/mail/commit/d6810da2a5d1fc60d632234865542962acdf7ddb))
* **ui:** resize action icons from 16px to 20px ([99fbdda](https://github.com/nextcloud/mail/commit/99fbdda306f7efd3c52bb98a4f4937f92eb2de2f))
* **ui:** resize button icons from 16px to 20px ([9c059b8](https://github.com/nextcloud/mail/commit/9c059b85d13ca4161f793d9fc2de537de66b726b))
* **ui:** resize inline icons from 16px to 20px ([e8a8d89](https://github.com/nextcloud/mail/commit/e8a8d89de6c92fd70fa2e1d5dbd9afe7c5837487))
* **ui:** revert composer session close icon to filled variant ([e10d9da](https://github.com/nextcloud/mail/commit/e10d9da41655955376268d3c342a63260b574aa0))
* **ui:** revert recipient delete icon to filled variant ([6468edb](https://github.com/nextcloud/mail/commit/6468edb42a4248312c91d3570dac4a133a610dcd))
* **ui:** show box-shadow for fluid design button all the time ([03b5861](https://github.com/nextcloud/mail/commit/03b5861d70b854704caa47269cbb999f583fe057))
* **ui:** use fluid background image for the setup view ([f88dec6](https://github.com/nextcloud/mail/commit/f88dec649b44f9ab8e38a767016a4e1c26ba7235))
* **ui:** use gradient as fallback for themed fluid design ([b3ebb27](https://github.com/nextcloud/mail/commit/b3ebb2787814f4c075f495b1eb04ea66f00dee2a))
* update wording for mailvelope ([b51ed32](https://github.com/nextcloud/mail/commit/b51ed32d53b4336ab7d7aa76d366a557f09738ce))
* white space between section titles ([9469c7b](https://github.com/nextcloud/mail/commit/9469c7be51d0743cb722f110263700d126cb218c))


### Features

* adjust background sync on user activity ([febaba1](https://github.com/nextcloud/mail/commit/febaba187da4dd60410ccfad738fe80d5af619fa))
* **ai-integration:** detect if message needs translation ([4a2b238](https://github.com/nextcloud/mail/commit/4a2b2389f31f084d8b7856f4a4bd84ed22c27f41))
* **deps:** add Nextcloud 33 support ([df5795d](https://github.com/nextcloud/mail/commit/df5795dab59e95fd43a00e84c82544ada65a43ea))
* introduce quick actions ([cc1047c](https://github.com/nextcloud/mail/commit/cc1047c7c884711144e229174914c102f8a5fd13))
* make summary thread similar to normal thread style ([997c19e](https://github.com/nextcloud/mail/commit/997c19e0553c9ee7ecc244e778c006e849d2ea8f))
* make thread elements have the same space around it ([ec2173c](https://github.com/nextcloud/mail/commit/ec2173c77ab9a6266dbb3499a32350df8b7d180f))
* quick actions frontend ([97fb6a5](https://github.com/nextcloud/mail/commit/97fb6a516ba8510c054e9e7740f5bce72601f563))
* reply message with meeting ([37be07c](https://github.com/nextcloud/mail/commit/37be07c664b5b3aad2c4d9135f9412766edd7cd1))
* split envelopes by time of arrival ([eaa7405](https://github.com/nextcloud/mail/commit/eaa740528c1284b79aa3291bcbfd20f907608f64))
* **ui:** add fluid design ([85f0628](https://github.com/nextcloud/mail/commit/85f06283836e7243496c20d37d2565aafcb0f6ea))



# [5.2.0-beta.1](https://github.com/nextcloud/mail/compare/v5.2.0-alpha.1...v5.2.0-beta.1) (2025-08-04)


### Bug Fixes

* add html and source editing support ([ff1f7d8](https://github.com/nextcloud/mail/commit/ff1f7d885c3c06850e08e969c5b6b35af8bc8c81))
* add translator note for account settings folder search ([a702a23](https://github.com/nextcloud/mail/commit/a702a233388163a3898b993bb0d5a43415e28192))
* **avatar:** handle null bodies ([b8f452b](https://github.com/nextcloud/mail/commit/b8f452bba27a059681f33b7842aa2b5c0ab2cc57))
* avoid invalid false return type ([d0f4b55](https://github.com/nextcloud/mail/commit/d0f4b556531a1271f11f3164093eb62699f48a28))
* button aria label and roles ([e38d831](https://github.com/nextcloud/mail/commit/e38d831869cbf9ef38d951b3c2d6fa3da2c0790d))
* **caldav:** invalid import of davclient ([376942d](https://github.com/nextcloud/mail/commit/376942d81cf95b462196aca6f629f7bfc258a24c))
* **classification:** use global default for new messages too ([d4e171b](https://github.com/nextcloud/mail/commit/d4e171bdac87390067fe5f4b0db10adef1dfcbe7))
* close modal when send is clicked ([3ef6947](https://github.com/nextcloud/mail/commit/3ef694729bfbba27767b70c5219d772e2553c8fa))
* **composer:** allow attaching shared files again ([214680a](https://github.com/nextcloud/mail/commit/214680adb43fd6216dd7c8ab8da5595e48c8c718))
* **deps:** Apply npm audit fix ([3c447b7](https://github.com/nextcloud/mail/commit/3c447b769a2b9e296ab8ac8adb91a1f15b0601a4))
* **deps:** Apply npm audit fix ([3822226](https://github.com/nextcloud/mail/commit/3822226ff7cde3f6d616b3a904888517b452ab8c))
* **deps:** Apply npm audit fix ([6e90100](https://github.com/nextcloud/mail/commit/6e9010004196f21b30da57773e4eb6ff688fc063))
* **deps:** Apply npm audit fix ([f538159](https://github.com/nextcloud/mail/commit/f538159f3abf69672a4df4498862b88676bc9c92))
* **deps:** bump @iframe-resizer/child from 5.4.6 to ^5.4.7 (main) ([#11374](https://github.com/nextcloud/mail/issues/11374)) ([41547fa](https://github.com/nextcloud/mail/commit/41547faa6bd9e9e672d49e5659e48b3f74aadf37))
* **deps:** bump @iframe-resizer/child from 5.4.7 to ^5.5.0 (main) ([#11430](https://github.com/nextcloud/mail/issues/11430)) ([b0947e6](https://github.com/nextcloud/mail/commit/b0947e64704972de8e2229d3ad0c0c1446ce0d17))
* **deps:** bump @iframe-resizer/parent from 5.4.6 to ^5.4.7 (main) ([#11375](https://github.com/nextcloud/mail/issues/11375)) ([c9fc35a](https://github.com/nextcloud/mail/commit/c9fc35aad941303316dada6e9508cf98d475b2e8))
* **deps:** bump @nextcloud/auth from 2.5.1 to ^2.5.2 (main) ([#11376](https://github.com/nextcloud/mail/issues/11376)) ([c15e8c3](https://github.com/nextcloud/mail/commit/c15e8c308336cc7645934f090c70ceb069667145))
* **deps:** bump @nextcloud/calendar-js from 8.1.2 to ^8.1.3 ([4c8363e](https://github.com/nextcloud/mail/commit/4c8363e9c3b36fef917b310e9c21092e37b52cb9))
* **deps:** bump @nextcloud/calendar-js from 8.1.3 to ^8.1.4 ([378405e](https://github.com/nextcloud/mail/commit/378405e2a7967ad795507275a4753af0cbaaf622))
* **deps:** bump @nextcloud/cdav-library from 1.5.3 to v2 ([08bff41](https://github.com/nextcloud/mail/commit/08bff4199ee5f1e3adfde894d4aaccbe7dafecdb))
* **deps:** bump @nextcloud/cdav-library from 2.1.0 to ^2.1.1 (main) ([#11409](https://github.com/nextcloud/mail/issues/11409)) ([2914a63](https://github.com/nextcloud/mail/commit/2914a6312556302b179eb602888bf31921d05d4d))
* **deps:** bump @nextcloud/dialogs from 5.3.8 to v6 ([53cfc3e](https://github.com/nextcloud/mail/commit/53cfc3ef45446903a13c70cb3dae76bc5a878195))
* **deps:** bump @nextcloud/l10n from 3.2.0 to ^3.3.0 (main) ([#11227](https://github.com/nextcloud/mail/issues/11227)) ([ed941e8](https://github.com/nextcloud/mail/commit/ed941e86f1dcbc8e81da99417e406eb83ebb0649))
* **deps:** bump @nextcloud/l10n from 3.4.0 to ^3.4.0 (main) ([#11427](https://github.com/nextcloud/mail/issues/11427)) ([e9f785c](https://github.com/nextcloud/mail/commit/e9f785c9d532fe21fd77a52d288702f1cf9240e2))
* **deps:** bump @nextcloud/moment from 1.3.4 to ^1.3.5 (main) ([#11410](https://github.com/nextcloud/mail/issues/11410)) ([29f0f8e](https://github.com/nextcloud/mail/commit/29f0f8ef908e2c70ebec94d0aac6e52795f565ef))
* **deps:** bump @nextcloud/sharing from 0.2.4 to ^0.2.5 ([f4f3535](https://github.com/nextcloud/mail/commit/f4f35358b044213e2938a1a9bf5412fed7f94323))
* **deps:** bump @nextcloud/vue from 8.27.0 to ^8.27.0 ([ef701cb](https://github.com/nextcloud/mail/commit/ef701cbf0c216469ee16012e50bc08759e80658d))
* **deps:** bump amphp/parallel and amphp/process for PHP8.4 compat ([8d0ef72](https://github.com/nextcloud/mail/commit/8d0ef72c8fe3b840a7a17c31159ee18d58453438))
* **deps:** bump core-js from 3.42.0 to ^3.43.0 (main) ([#11256](https://github.com/nextcloud/mail/issues/11256)) ([8b9a531](https://github.com/nextcloud/mail/commit/8b9a531b9c7d8dfefe221a1bff30aaac0254d87d))
* **deps:** bump dompurify from 3.2.5 to ^3.2.6 (main) ([#11180](https://github.com/nextcloud/mail/issues/11180)) ([fbbf4bf](https://github.com/nextcloud/mail/commit/fbbf4bfd15000113755fa37d76634683c10282d1))
* **deps:** bump form-data to v4.0.4 ([71d52ca](https://github.com/nextcloud/mail/commit/71d52ca16c25bc19f5e84f54c41bca8433706d03))
* **deps:** bump ical.js from 2.1.0 to ^2.2.0 (main) ([#11343](https://github.com/nextcloud/mail/issues/11343)) ([10d46f5](https://github.com/nextcloud/mail/commit/10d46f5714f081a487a84cdae353e23fb266c7fc))
* **deps:** bump nextcloud/kitinerary-bin from 1.0.3 to ^1.0.4 (main) ([#11181](https://github.com/nextcloud/mail/issues/11181)) ([cc4b6af](https://github.com/nextcloud/mail/commit/cc4b6afa0dca2559be3f7253c7d41598c833b5d3))
* **deps:** bump ramda from 0.30.1 to ^0.31.3 ([0f5d1cb](https://github.com/nextcloud/mail/commit/0f5d1cba25f079fb36ca802ed37872c05b2f9d66))
* **deps:** bump stylelint from 16.19.1 to ^16.20.0 (main) ([#11248](https://github.com/nextcloud/mail/issues/11248)) ([0d95338](https://github.com/nextcloud/mail/commit/0d95338d7597d7f77d691ea921a6bc8e295cd01c))
* **deps:** bump webdav from 4.11.4 to v4.11.5 (main) ([#11246](https://github.com/nextcloud/mail/issues/11246)) ([6359d2b](https://github.com/nextcloud/mail/commit/6359d2b9e6e1c7ca9fa6b40343b62b9ee4cea1b4))
* **dns:** Update public suffix list ([6096398](https://github.com/nextcloud/mail/commit/6096398a88ea3065ddf48afa895d1a70c6e9d4fd))
* **dns:** Update public suffix list ([990031a](https://github.com/nextcloud/mail/commit/990031a8be090ff5fe55bab5647f8be921d0536f))
* **dns:** Update public suffix list ([5ea2259](https://github.com/nextcloud/mail/commit/5ea2259e2963106d27fcdae78119b43fa322d0a5))
* **dns:** Update public suffix list ([fb7762c](https://github.com/nextcloud/mail/commit/fb7762c3ac5fe23845b04540b0a7f63211055791))
* **dns:** Update public suffix list ([f8bf4ec](https://github.com/nextcloud/mail/commit/f8bf4eccbd37a6a529d365e4d7e60889f9b56b08))
* don't propagate click event on reply ([56ac6af](https://github.com/nextcloud/mail/commit/56ac6af0c3f2f738d64797538530c84f66c9a008))
* **filters:** Use contains operator for from and to header ([8d79ff2](https://github.com/nextcloud/mail/commit/8d79ff231cec394d4e90b2b429f1df8e55650936))
* html message alignment ([7fde440](https://github.com/nextcloud/mail/commit/7fde440d186faf3bf7724144d56b169db7f03ade))
* **imip:** hide warning about email mismatch for imip replies ([aca57c4](https://github.com/nextcloud/mail/commit/aca57c45338b542899c25ec02c6172bca66982fa))
* **imip:** process imip messages more frequently ([881c2a5](https://github.com/nextcloud/mail/commit/881c2a593dffa9dfe3c30cdb0a59103ed1f02623))
* invitation title on small screen ([d268bb8](https://github.com/nextcloud/mail/commit/d268bb8f5e7e5b4c2f68020a7033f9b02fdecf8a))
* **l10n:** remove trailing space and allow RTL for drafts ([a9acabc](https://github.com/nextcloud/mail/commit/a9acabca1240eeb2d96c32611beed708e5a958c6))
* **l10n:** Update translations from Transifex ([f341e23](https://github.com/nextcloud/mail/commit/f341e236d868d252d930f90d5aa407a9d775598f))
* **l10n:** Update translations from Transifex ([7520191](https://github.com/nextcloud/mail/commit/7520191dfbc3b848bb990fd0c8dab0fcebc2ca8c))
* **l10n:** Update translations from Transifex ([c9eb41d](https://github.com/nextcloud/mail/commit/c9eb41d382944c6293b222f9daff982836437d6f))
* **l10n:** Update translations from Transifex ([34ee755](https://github.com/nextcloud/mail/commit/34ee75530288bda133637101eb5a5dad1f22a82e))
* **l10n:** Update translations from Transifex ([be60d8b](https://github.com/nextcloud/mail/commit/be60d8bd43af9455a44e678bd06359a7cb9532c2))
* **l10n:** Update translations from Transifex ([b1d0931](https://github.com/nextcloud/mail/commit/b1d0931b0d85fec5d76740446836f5d086d929da))
* **l10n:** Update translations from Transifex ([0911657](https://github.com/nextcloud/mail/commit/0911657911162a5fec675b22b38510bde70fdc18))
* **l10n:** Update translations from Transifex ([b0aa0c8](https://github.com/nextcloud/mail/commit/b0aa0c8edbf4c77d061976a2513e6d17a8fc7944))
* **l10n:** Update translations from Transifex ([8d02123](https://github.com/nextcloud/mail/commit/8d021232fc5a74ebe9f706e4bd0f0c9d0b165ae0))
* **l10n:** Update translations from Transifex ([4e860ae](https://github.com/nextcloud/mail/commit/4e860aed21035bd4dc49ea699c1fd25436cd3c4b))
* **l10n:** Update translations from Transifex ([fe04747](https://github.com/nextcloud/mail/commit/fe047476f447551da02d066ee5f0d4015ae0ab94))
* **l10n:** Update translations from Transifex ([6568fd8](https://github.com/nextcloud/mail/commit/6568fd8858fac930eb67e45c0fbea675439c21c1))
* **l10n:** Update translations from Transifex ([a79ef67](https://github.com/nextcloud/mail/commit/a79ef6780061910d8010a5fb5d881656202f2f5d))
* **l10n:** Update translations from Transifex ([2590c76](https://github.com/nextcloud/mail/commit/2590c76c2dbb8ea52435f6e648a51d104c590a0b))
* **l10n:** Update translations from Transifex ([b741e2d](https://github.com/nextcloud/mail/commit/b741e2d0da217a63bcfd3cdbe0edfe97357b1824))
* **l10n:** Update translations from Transifex ([45cbe3f](https://github.com/nextcloud/mail/commit/45cbe3f8ef9c3e855ddbeed2487811d767c8c0a0))
* **l10n:** Update translations from Transifex ([ee76fff](https://github.com/nextcloud/mail/commit/ee76fffca11451414c40b60bad49e4486e49de81))
* **l10n:** Update translations from Transifex ([4dd274f](https://github.com/nextcloud/mail/commit/4dd274ffe670e752b8ccaf0415e866e9f16b2744))
* **l10n:** Update translations from Transifex ([6002661](https://github.com/nextcloud/mail/commit/600266130fd8d931e46359c4b8c18ca94b248a83))
* **l10n:** Update translations from Transifex ([c92032c](https://github.com/nextcloud/mail/commit/c92032c7f08fcc59a223dc621a2ac218e43aa208))
* **l10n:** Update translations from Transifex ([5d4a2be](https://github.com/nextcloud/mail/commit/5d4a2becb7e8bbce8788579af155a712f4933a60))
* **l10n:** Update translations from Transifex ([5b2695e](https://github.com/nextcloud/mail/commit/5b2695e63cbc07dd56eae99a51b36d6745a67e56))
* **l10n:** Update translations from Transifex ([3f79191](https://github.com/nextcloud/mail/commit/3f79191fdf2a68f164ded7233fa7da7eac056945))
* **l10n:** Update translations from Transifex ([4e381eb](https://github.com/nextcloud/mail/commit/4e381ebbaf99b182867efcadef2f00fb596768de))
* **l10n:** Update translations from Transifex ([5d13aac](https://github.com/nextcloud/mail/commit/5d13aacd343883b2c7ace01db7280a0664c0a6e4))
* **l10n:** Update translations from Transifex ([d82e8f3](https://github.com/nextcloud/mail/commit/d82e8f3c3398ad54c921217f5b1238dc8683bebc))
* **l10n:** Update translations from Transifex ([b8bcc35](https://github.com/nextcloud/mail/commit/b8bcc35a47105712031485bd0660503c709bafa9))
* **l10n:** Update translations from Transifex ([98f36b1](https://github.com/nextcloud/mail/commit/98f36b146dd5ccc8ba6a22717349185a0429759a))
* **l10n:** Update translations from Transifex ([a64a503](https://github.com/nextcloud/mail/commit/a64a50389ccb64786ad7078641d453468b6da152))
* **l10n:** Update translations from Transifex ([3a7babe](https://github.com/nextcloud/mail/commit/3a7babefc2c3c76c69cc89caf4eb88f89b3c14cf))
* **l10n:** Update translations from Transifex ([7e8dfe2](https://github.com/nextcloud/mail/commit/7e8dfe265a2a5ad9d767bcd062ebcd7fa3810971))
* **l10n:** Update translations from Transifex ([e5de9ef](https://github.com/nextcloud/mail/commit/e5de9ef94d16d1c7605dd93d1ef71ceabdeb9bf4))
* **l10n:** Update translations from Transifex ([5e1fb1c](https://github.com/nextcloud/mail/commit/5e1fb1c549ee5436974393f2a5a957b3094847c6))
* **l10n:** Update translations from Transifex ([0e37608](https://github.com/nextcloud/mail/commit/0e376080c9bc14146fd0b1db06472c5aa47b64ab))
* **l10n:** Update translations from Transifex ([1ad5068](https://github.com/nextcloud/mail/commit/1ad5068566ed773876a7f6c7a92baab925438fe1))
* **l10n:** Update translations from Transifex ([ebfc9c0](https://github.com/nextcloud/mail/commit/ebfc9c0b13e558e88cf7e11677fb32354931ba1d))
* **l10n:** Update translations from Transifex ([ddd3fd3](https://github.com/nextcloud/mail/commit/ddd3fd3e0318fc102dcb2dfd14e348408cda6181))
* **l10n:** Update translations from Transifex ([899046c](https://github.com/nextcloud/mail/commit/899046c5345fc682f11f2c938f1971789fe5e9fe))
* **l10n:** Update translations from Transifex ([e21d952](https://github.com/nextcloud/mail/commit/e21d9529f8659534c0645ab9b056094af1dafbcb))
* **l10n:** Update translations from Transifex ([6ee4694](https://github.com/nextcloud/mail/commit/6ee4694cad7f8605e1df3951b9e15b627c611e77))
* **l10n:** Update translations from Transifex ([8ddec2b](https://github.com/nextcloud/mail/commit/8ddec2b73fee49c931f05fc3e80fd1aa2c369c88))
* **l10n:** Update translations from Transifex ([7f67183](https://github.com/nextcloud/mail/commit/7f67183afb452d490f705124b98f3e4930c1e3dd))
* **l10n:** Update translations from Transifex ([646c18a](https://github.com/nextcloud/mail/commit/646c18a82da017dc0d1a0dc86f54015a90932e32))
* **l10n:** Update translations from Transifex ([ac1afec](https://github.com/nextcloud/mail/commit/ac1afec69835c489c4f71266d8cb66495a57ba4d))
* **l10n:** Update translations from Transifex ([31a5a3c](https://github.com/nextcloud/mail/commit/31a5a3c4e321f891470bd9b42211d8a0f9280b74))
* **l10n:** Update translations from Transifex ([d8e8c0a](https://github.com/nextcloud/mail/commit/d8e8c0a7d8b9d256faa8e76e6764ae3342970637))
* **l10n:** Update translations from Transifex ([ddb6ffb](https://github.com/nextcloud/mail/commit/ddb6ffb4754cf6290aa814e88e89738a2dd1faf3))
* **l10n:** Update translations from Transifex ([9ab75d1](https://github.com/nextcloud/mail/commit/9ab75d104a0fee24fa02aa31d216fc74b0351726))
* **l10n:** Update translations from Transifex ([497cb7e](https://github.com/nextcloud/mail/commit/497cb7e54e77cc4c675b43a25aafd57e58b66157))
* **l10n:** Update translations from Transifex ([b01ef9a](https://github.com/nextcloud/mail/commit/b01ef9ab50e6cdbf1e6a0060c20aaa9cd9ff6186))
* **l10n:** Update translations from Transifex ([5f70139](https://github.com/nextcloud/mail/commit/5f70139b99c827130cb6c2b58d1b29990eb08762))
* **l10n:** Update translations from Transifex ([8cc0405](https://github.com/nextcloud/mail/commit/8cc0405371a59dca96eafd2dc5242ddef334e53e))
* **l10n:** Update translations from Transifex ([3e67a02](https://github.com/nextcloud/mail/commit/3e67a025d0f7d17f11e84384bff2da22091f92d7))
* **l10n:** Update translations from Transifex ([2956088](https://github.com/nextcloud/mail/commit/29560884296487375d06376251ef50eb20621476))
* **l10n:** Update translations from Transifex ([2065780](https://github.com/nextcloud/mail/commit/2065780937de85c82a9fcbb4da664689687dfdff))
* **l10n:** Update translations from Transifex ([c4ddfe3](https://github.com/nextcloud/mail/commit/c4ddfe3aa011b9aec0651ee891b69aaf6ce0774e))
* **l10n:** Update translations from Transifex ([fdabd4a](https://github.com/nextcloud/mail/commit/fdabd4a217c424faf3a3a84f47fc4836af84d6a9))
* **l10n:** Update translations from Transifex ([d4f1083](https://github.com/nextcloud/mail/commit/d4f10833dc3f04b45db0027cf3c6804d2295739f))
* **l10n:** Update translations from Transifex ([1835be7](https://github.com/nextcloud/mail/commit/1835be760385d968bb3b089a454e5c668630d10d))
* **l10n:** Update translations from Transifex ([f366a5d](https://github.com/nextcloud/mail/commit/f366a5dd0fc3a308f679dfbc96876a70b2d0b081))
* **l10n:** Update translations from Transifex ([dea6367](https://github.com/nextcloud/mail/commit/dea63678030c407b81d5aaa19bfeee938a3cbdc3))
* **l10n:** Update translations from Transifex ([f559247](https://github.com/nextcloud/mail/commit/f559247a9a1f3ffc3be1ec9147a3005803796683))
* **l10n:** Update translations from Transifex ([4535685](https://github.com/nextcloud/mail/commit/4535685d63e02cb95a65db799c7d6be760f7846c))
* list layout height ([006fe78](https://github.com/nextcloud/mail/commit/006fe78cc1e666ecc57169bededb5cb2824d4514))
* load-more button alignment ([1a56aa2](https://github.com/nextcloud/mail/commit/1a56aa29f790b9e4866fd3f161729524753a469f))
* mail heading on settings shouldnt be skipped ([c34fb46](https://github.com/nextcloud/mail/commit/c34fb46e0cfbaf41a756948a8a8ac903ee5aaeeb))
* mailvelope button colour text ([5aff985](https://github.com/nextcloud/mail/commit/5aff9858bc3096ce1010fa76a448bb1e0ee1b0f1))
* make accounts property reactive ([6054a9a](https://github.com/nextcloud/mail/commit/6054a9a943dadfe177ff7418e6a1fafc078cbc40))
* make saving account settings work again ([9740e56](https://github.com/nextcloud/mail/commit/9740e5618fef1b7d51831572dfa82f56f44204fd))
* moving messages without a message id on servers without UIDPLUS ([1c8a90e](https://github.com/nextcloud/mail/commit/1c8a90ed5f526c05079efb1ffeca373bb0abb4c2))
* **outbox:** outbox do not render ([31be037](https://github.com/nextcloud/mail/commit/31be037d648adc605e7ea1ec2d16648eea297ad1))
* prevent invalid return type ([172de5e](https://github.com/nextcloud/mail/commit/172de5e3445b8be9b06ab118e9ebdf6e84a8a21c))
* recipient length is undefined ([f0b07b8](https://github.com/nextcloud/mail/commit/f0b07b88d96834c3d1ba6b1417800c3ec9ced7ac))
* remove redendant css ([afd7741](https://github.com/nextcloud/mail/commit/afd774184f731a751240cadcafcaee381180af39))
* remove source editing plugin ([070013d](https://github.com/nextcloud/mail/commit/070013dc97d053083581305182b6a45e7fcde181))
* remove unused and broken computed menu property ([2359051](https://github.com/nextcloud/mail/commit/23590514ccc7f8d1cefd6a20bbb7fae11aa6f7a7))
* rename collapse label ([b36dfb6](https://github.com/nextcloud/mail/commit/b36dfb6b474bcdc231d0ca96ebcde685cdb6842e))
* **smime:** only accept certificates and keys in file input ([9778add](https://github.com/nextcloud/mail/commit/9778addf9ce767645518c112693d5f6bd804ed46))
* **smime:** persist sign preference per alias ([4863f96](https://github.com/nextcloud/mail/commit/4863f96d3b75d792d211f9ed28f25db049e9dca2))
* text block wording / spelling ([891d4dd](https://github.com/nextcloud/mail/commit/891d4dd34b3ea102d6107220853d13bdbb711eb3))
* **uid:** switch back from unread circle to dot ([0ee8f3c](https://github.com/nextcloud/mail/commit/0ee8f3cf790b722ca49defe5856fdb7c35e0601d))
* **uid:** use outline erasor icon for clear mailbox action ([dd27fa5](https://github.com/nextcloud/mail/commit/dd27fa5cdb6c06af2cc4d47ff6131a52d3ff115f))
* **ui:** flip unread icons for envelopes ([3b6f756](https://github.com/nextcloud/mail/commit/3b6f75682ce5686a57d774f55d06215e6dd21574))
* **ui:** give file pickers a name ([a1a4ca0](https://github.com/nextcloud/mail/commit/a1a4ca0251939d03abd50becdeab23f63a92a61a))
* **ui:** go back to filled icons for selection and confirmation icons ([1adecd1](https://github.com/nextcloud/mail/commit/1adecd1bd3ab449dd38b358ae8f695ba64798045))
* **ui:** make composer link attachments icon size wider ([de6ab08](https://github.com/nextcloud/mail/commit/de6ab08dd3c3b6663872ee2c12e5e540e7c12790))
* **ui:** replace deprecated iframe resizer attribute ([d74cf2d](https://github.com/nextcloud/mail/commit/d74cf2dd7938df3842ed5e85609a37550d55a60b))
* **ui:** show only drafts with draft prefix ([edba111](https://github.com/nextcloud/mail/commit/edba1114aa1c223331776ea2f53ae330176105ec))
* **ui:** use filled/outline icon for the important flag ([20c57d9](https://github.com/nextcloud/mail/commit/20c57d9cc273b52b8d0242327f8cab1cab8a96ff))
* **ui:** use outline icon for archiving actions ([a2fb5d6](https://github.com/nextcloud/mail/commit/a2fb5d640678f3fc0f88acc910a22345abb7b46f))
* update summarizeMessages-prompt to hide any introduction ([#11278](https://github.com/nextcloud/mail/issues/11278)) ([49c83e4](https://github.com/nextcloud/mail/commit/49c83e4ba32af8b86c9644da35ec9dedfaddec33))


### Features

* add more formatting features on ckeditor ([60d0ed6](https://github.com/nextcloud/mail/commit/60d0ed637f2ac15d7af6902bd4afe23af4b13007))
* add OCS routes for mailbox and mail listing ([0b86510](https://github.com/nextcloud/mail/commit/0b86510877a97236c5f81bbad3bb3a1deb2e657c))
* Allow disabling of threaded view ([084cc67](https://github.com/nextcloud/mail/commit/084cc6768aa3f118c4b233b68b57eee5d698818d))
* change mailbox text to folder ([9287321](https://github.com/nextcloud/mail/commit/9287321f9bdf0e5ea9d50b38a0ea927b5061a9ee))
* **composer:** suggest recipients from own identities ([6fc54b4](https://github.com/nextcloud/mail/commit/6fc54b4c246ce6083fe3b5755e44eb6a134f2132))
* make message source modal larger ([e397eb3](https://github.com/nextcloud/mail/commit/e397eb385b101e8ed358f8f5fe10b965d3051c7a))
* **sieve:** create a mail filter from a message ([11b2d07](https://github.com/nextcloud/mail/commit/11b2d070f661c852dfb4f1332e062be3f226bdbe))
* start loading additional envelopes earlier ([3663164](https://github.com/nextcloud/mail/commit/36631649f354b8ff5d04cf24634de06586a067de))
* text blocks ([fcbfc63](https://github.com/nextcloud/mail/commit/fcbfc63cca57f2f6bdb0d309b428606193ddca68))
* thread list shouldnt reload when searching ([665b3dd](https://github.com/nextcloud/mail/commit/665b3dd000d244b450658fed64bc6231c1c25f25))
* **ui:** switch to outline icons where possible ([e55bf97](https://github.com/nextcloud/mail/commit/e55bf97db580a14d9b218e87cf8f2f23b1be5242))


### Performance Improvements

* cache pre-fetched mailboxes on the HTTP level ([0c4ac27](https://github.com/nextcloud/mail/commit/0c4ac2724a525aa52924233713e7f9c5b458f1d0))
* improve quota loading for accounts ([2a9b9ae](https://github.com/nextcloud/mail/commit/2a9b9aed4229ab702f08f966bd4cb3c2b7d53ea8))
* prefetch other mailboxes in the background ([2957ec5](https://github.com/nextcloud/mail/commit/2957ec50b375e19633dd6ef4e0b936f160300de1))
* reduce number of avatar requests ([9e08477](https://github.com/nextcloud/mail/commit/9e08477c5cb515edcdeed81944aca009e902b51d))



# [5.2.0-alpha.1](https://github.com/nextcloud/mail/compare/v5.0.0-dev.1...v5.2.0-alpha.1) (2025-05-20)


### Bug Fixes

* ckeditor buttons dont show up correctly ([567e8b0](https://github.com/nextcloud/mail/commit/567e8b0157793d3ae0e836331bba4ff78749cdcc))
* **composer:** handling of plain and html bodies ([58c48ad](https://github.com/nextcloud/mail/commit/58c48ad0f20ac1487dcd0cf653b0a3a52506f45c))
* define appName and app version ([1938df0](https://github.com/nextcloud/mail/commit/1938df0e62cced0244fb3ac364bbb279a86497d8))
* **deps:** Apply npm audit fix ([c73ec4d](https://github.com/nextcloud/mail/commit/c73ec4d1596571bf08ee6d3e2b19cd55efba4217))
* **deps:** bump @iframe-resizer/child from 5.4.2 to ^5.4.4 (main) ([#10981](https://github.com/nextcloud/mail/issues/10981)) ([42eaa20](https://github.com/nextcloud/mail/commit/42eaa20014667443471af4546c64b4ab5be14fd1))
* **deps:** bump @iframe-resizer/child from 5.4.4 to ^5.4.5 (main) ([#11020](https://github.com/nextcloud/mail/issues/11020)) ([a40ed7b](https://github.com/nextcloud/mail/commit/a40ed7bbcef0c6d73f8ef805122f5942031a63d3))
* **deps:** bump @iframe-resizer/child from 5.4.5 to ^5.4.6 (main) ([#11044](https://github.com/nextcloud/mail/issues/11044)) ([1059d5a](https://github.com/nextcloud/mail/commit/1059d5a241d9305d17ce7a1e6a26901c6c22f9f9))
* **deps:** bump @iframe-resizer/parent from 5.4.3 to ^5.4.4 (main) ([#10982](https://github.com/nextcloud/mail/issues/10982)) ([e6b685c](https://github.com/nextcloud/mail/commit/e6b685c35676761d1201e743804ca6418c385234))
* **deps:** bump @iframe-resizer/parent from 5.4.4 to ^5.4.5 (main) ([#11021](https://github.com/nextcloud/mail/issues/11021)) ([c9f315b](https://github.com/nextcloud/mail/commit/c9f315ba6073d98b57893d0f91a1296b118569a1))
* **deps:** bump @iframe-resizer/parent from 5.4.5 to ^5.4.6 (main) ([#11045](https://github.com/nextcloud/mail/issues/11045)) ([6d3e064](https://github.com/nextcloud/mail/commit/6d3e06430f71f097670a611402d398797f36e960))
* **deps:** bump @nextcloud/auth from 2.4.0 to ^2.5.1 (main) ([#11138](https://github.com/nextcloud/mail/issues/11138)) ([3377509](https://github.com/nextcloud/mail/commit/33775097ecf80c2ae0ef3eee89c209a24077b9d6))
* **deps:** bump @nextcloud/l10n from 3.2.0 to ^3.2.0 ([4fff529](https://github.com/nextcloud/mail/commit/4fff529156ca9c8892bb3c7dc93b230d07b92927))
* **deps:** bump @nextcloud/moment from 1.3.2 to ^1.3.4 (main) ([#11098](https://github.com/nextcloud/mail/issues/11098)) ([2df3423](https://github.com/nextcloud/mail/commit/2df3423153dc8aad40dfc9a54227f049711f58a9))
* **deps:** bump @nextcloud/vue from 8.23.1 to ^8.25.1 ([3d96e54](https://github.com/nextcloud/mail/commit/3d96e54a032a4e089aa70bdccad002246450aab9))
* **deps:** bump @nextcloud/vue from 8.25.1 to ^8.26.0 ([986ca1c](https://github.com/nextcloud/mail/commit/986ca1c273427030d1c90b020b28d72a6bd77aaf))
* **deps:** bump @nextcloud/vue from 8.26.0 to ^8.26.1 ([d66a6b0](https://github.com/nextcloud/mail/commit/d66a6b09eb906f7e7939fad6e089e7c3872e0db4))
* **deps:** bump bytestream/horde-imap-client from 2.33.5 to ^2.33.6 (main) ([#11099](https://github.com/nextcloud/mail/issues/11099)) ([ebdb084](https://github.com/nextcloud/mail/commit/ebdb084abfb774d7f18554091c659585cf6b369d))
* **deps:** bump calendar-js and timezones ([e8a79d7](https://github.com/nextcloud/mail/commit/e8a79d7b5cbf888fd242aac68468c930af382a9e))
* **deps:** bump core-js from 3.41.0 to ^3.42.0 (main) ([#11092](https://github.com/nextcloud/mail/issues/11092)) ([c0c4beb](https://github.com/nextcloud/mail/commit/c0c4bebfc0fca4153023e639d6d49ae794c2745d))
* **deps:** bump dompurify from 3.2.4 to ^3.2.5 (main) ([#11005](https://github.com/nextcloud/mail/issues/11005)) ([6a72602](https://github.com/nextcloud/mail/commit/6a726028d9cc8412d4e969c1e12fbced0ab7a1f6))
* **deps:** bump iframe-resizer from 5.3.3 to ^5.4.6 (main) ([#11093](https://github.com/nextcloud/mail/issues/11093)) ([e4f36be](https://github.com/nextcloud/mail/commit/e4f36be14c6b9d51a84f85c05ee949cf59d21abd))
* **deps:** bump jeremykendall/php-domain-parser from 6.3.1 to ^6.4.0 (main) ([#11106](https://github.com/nextcloud/mail/issues/11106)) ([aaae668](https://github.com/nextcloud/mail/commit/aaae668160421df359e699641e9f7081b8a73483))
* **deps:** bump nextcloud/kitinerary-sys from 1.0.1 to v2 ([e716eea](https://github.com/nextcloud/mail/commit/e716eeaa04ef5618b824117e54a92b4b6e6207f8))
* **deps:** bump stylelint from 16.16.0 to ^16.19.1 (main) ([#11102](https://github.com/nextcloud/mail/issues/11102)) ([eec3762](https://github.com/nextcloud/mail/commit/eec376250ee2b1a4042cb2268ae0005928b0a504))
* **deps:** pin @ckeditor/ckeditor5-dev-utils from 44.2.1 to 44.2.1 ([1684833](https://github.com/nextcloud/mail/commit/16848331e0896068e6858a863ffa77171e4edbe2))
* **deps:** update ckeditor dev utils to 44 ([22a8e56](https://github.com/nextcloud/mail/commit/22a8e56dd32edb7bf973a4b91c300e33229c9c41))
* **deps:** update vulnerable babel packages ([4c9d2c1](https://github.com/nextcloud/mail/commit/4c9d2c1de566ec41f38c71aef6c800223e48e994))
* **dns:** Update public suffix list ([19806ea](https://github.com/nextcloud/mail/commit/19806ea55be671cad2f5050f804e3be7e651ce97))
* **dns:** Update public suffix list ([495a20c](https://github.com/nextcloud/mail/commit/495a20cb6440b425cfcff96449ec20f5a0a7e51a))
* **dns:** Update public suffix list ([228c719](https://github.com/nextcloud/mail/commit/228c71973ea28c614e5e8835b0426365fe031a7a))
* don't show important or unread emails in trash ([ce2f949](https://github.com/nextcloud/mail/commit/ce2f9497f9a76c27d550a9bb9177eb4067a5eee9))
* handle recurring events and show better message to user ([a7dce92](https://github.com/nextcloud/mail/commit/a7dce926c8a649b7c980394b29b52315a96d0c65))
* **imap:** handle password decryption exception ([033433b](https://github.com/nextcloud/mail/commit/033433bc497ef14f33417ddbee93e6252578032a))
* **imip:** use default calendar if possible ([3cdd8d3](https://github.com/nextcloud/mail/commit/3cdd8d3fcf92d0764f4c94c6014348aa0cf160dc))
* **l10n:** Update translations from Transifex ([452383d](https://github.com/nextcloud/mail/commit/452383da63f708a1579fc691e4b891c14df9792a))
* **l10n:** Update translations from Transifex ([df74690](https://github.com/nextcloud/mail/commit/df746905e779c8875c00ad5b929310e5333717be))
* **l10n:** Update translations from Transifex ([6fbdad0](https://github.com/nextcloud/mail/commit/6fbdad0de9ec3c5175492a27d6f759f51aedd4a6))
* **l10n:** Update translations from Transifex ([e8d3dbf](https://github.com/nextcloud/mail/commit/e8d3dbf6e5aba9be730f8fccd97bae7bb91de959))
* **l10n:** Update translations from Transifex ([ccc4223](https://github.com/nextcloud/mail/commit/ccc422353ca91583ff63470f13c0c6c0993ef403))
* **l10n:** Update translations from Transifex ([a51a5e0](https://github.com/nextcloud/mail/commit/a51a5e0fa825e5dcae81f92a08ce7dfef053c413))
* **l10n:** Update translations from Transifex ([3de2615](https://github.com/nextcloud/mail/commit/3de2615c20d7e541b5706c2d5e221a09a4b6badf))
* **l10n:** Update translations from Transifex ([a74e4d9](https://github.com/nextcloud/mail/commit/a74e4d93b0c9e4934eb12565133faf905df0e570))
* **l10n:** Update translations from Transifex ([6c19578](https://github.com/nextcloud/mail/commit/6c195780892e916c360a07fd191ad1196e4878bf))
* **l10n:** Update translations from Transifex ([0874560](https://github.com/nextcloud/mail/commit/08745605bbaa978259473cdccc5d3769d760b341))
* **l10n:** Update translations from Transifex ([7bbcb29](https://github.com/nextcloud/mail/commit/7bbcb294612776ee56f685335b2376f0f682bc20))
* **l10n:** Update translations from Transifex ([bae559f](https://github.com/nextcloud/mail/commit/bae559f86be5e14a01803ab14fd4e3d8620bb93c))
* **l10n:** Update translations from Transifex ([20710d4](https://github.com/nextcloud/mail/commit/20710d41abedcf8a3a3fc0e05fd1a1fcfd1cb3a1))
* **l10n:** Update translations from Transifex ([df20def](https://github.com/nextcloud/mail/commit/df20def233fe29f47a2a9f1a190a56a047ac88be))
* **l10n:** Update translations from Transifex ([d07aed4](https://github.com/nextcloud/mail/commit/d07aed4ef3323e63d52b38eeb8caa429998d6ca1))
* **l10n:** Update translations from Transifex ([ecbb3da](https://github.com/nextcloud/mail/commit/ecbb3dadf5ec8618e5dd539b2c229611c6a87a7f))
* **l10n:** Update translations from Transifex ([cfce105](https://github.com/nextcloud/mail/commit/cfce1058c549eacb5b88f2baaaadc8dd37c8c35a))
* **l10n:** Update translations from Transifex ([82fabe2](https://github.com/nextcloud/mail/commit/82fabe2fb5b1df86e12f9b7ac5c7b5df8a421fe0))
* **l10n:** Update translations from Transifex ([a445ac0](https://github.com/nextcloud/mail/commit/a445ac09c8bdf5ae58e8a3ee9f6a4eb07b923159))
* **l10n:** Update translations from Transifex ([c96ec6e](https://github.com/nextcloud/mail/commit/c96ec6e3fd719734c51430a42a680dbb7dc1b279))
* **l10n:** Update translations from Transifex ([c59cf84](https://github.com/nextcloud/mail/commit/c59cf8449f9cef249443fcc911acb531bb386f0f))
* **l10n:** Update translations from Transifex ([49fbc16](https://github.com/nextcloud/mail/commit/49fbc16089325d4a549a4a20e7c6f9a98c5dc1e4))
* **l10n:** Update translations from Transifex ([a59c212](https://github.com/nextcloud/mail/commit/a59c212ebf9905bc10b12f706e1d096cc080e18d))
* **l10n:** Update translations from Transifex ([fece38e](https://github.com/nextcloud/mail/commit/fece38e43091817273dbdfcfb75214e005fbed3f))
* **l10n:** Update translations from Transifex ([abf1c6b](https://github.com/nextcloud/mail/commit/abf1c6b2b30939dc2ea742ae81653e537c336485))
* **l10n:** Update translations from Transifex ([4a35bdd](https://github.com/nextcloud/mail/commit/4a35bdd379f1459228a47611ea0e03a37d28f41d))
* **l10n:** Update translations from Transifex ([8a205d0](https://github.com/nextcloud/mail/commit/8a205d087885f74f7af092bf45d6e2d2d9ddc669))
* **l10n:** Update translations from Transifex ([39faeeb](https://github.com/nextcloud/mail/commit/39faeebe71789e0c7babe62cad072d439e6e1622))
* **l10n:** Update translations from Transifex ([835327e](https://github.com/nextcloud/mail/commit/835327eaa4b4fa33a060fcb8a2e760f36c7e0335))
* mark envelope as unseen via shortcut ([35f1d1d](https://github.com/nextcloud/mail/commit/35f1d1dacada93a1198b82b3e32128467cfa45d2))
* **mime:** ignore HTML parsing errors consistently ([0b682eb](https://github.com/nextcloud/mail/commit/0b682eb1ee1d5b61d818f478c4e457dd5391c3f7))
* moving nested mailboxes ([41f3378](https://github.com/nextcloud/mail/commit/41f3378cbc6c2f10a6716e8d8f75eee2f3a5a878))
* patchAccountiMutation is not defined ([a33f3b7](https://github.com/nextcloud/mail/commit/a33f3b7bdb085f0c9d8f14a1afcad7bead4858fa))
* **phishing:** strip exactly 1 character from the end of a link string to remove brackets ([c646917](https://github.com/nextcloud/mail/commit/c64691795b497a1ab0961d4c7e9830153be43b2a))
* prevent dragging mailboxes ([7b69ed3](https://github.com/nextcloud/mail/commit/7b69ed338b500233eb7f830a972b964c774977f0))
* preview enhancement process job does not process messages ([680b90d](https://github.com/nextcloud/mail/commit/680b90dc7d5c7843f2755a9e60722eecc042f46d))
* show submailboxes for filtering ([4874ee4](https://github.com/nextcloud/mail/commit/4874ee4bf7f3192e7dc98ee9165773d74e894c32))
* show warning when creating a mailbox fails ([ecc47e0](https://github.com/nextcloud/mail/commit/ecc47e05331204f07e6ed8d4dc62eca1c55e6922))
* **smime:** handle encoding properly when signing and encrypting ([f12ffe5](https://github.com/nextcloud/mail/commit/f12ffe50a6ebae6501d9aa798fb3959884693755))
* **smime:** use proper binary encoding when signing messages ([646bdae](https://github.com/nextcloud/mail/commit/646bdae4ae7373783069eb6d83fa2c73d22f9ea5))
* **ui:** handle error when saving email attachments to Files ([e4a625c](https://github.com/nextcloud/mail/commit/e4a625c459757f9622e0e31ab369ec10732f545d))
* Undefined array key issue ([00e65cf](https://github.com/nextcloud/mail/commit/00e65cf8d8c167693662d3c7a9774988ae8f6cfe))
* use alias name for from header ([c7a1724](https://github.com/nextcloud/mail/commit/c7a17248fd33d21cb745eda46b400faa24be2cb6))


### Features

* close modal on send ([073038d](https://github.com/nextcloud/mail/commit/073038dd4bb79c654e64167bd93416af908b4337))
* enable account debugging for sieve ([3a9385e](https://github.com/nextcloud/mail/commit/3a9385e058d614e59dd56cb1dfdae64a7fac412c))
* per account imap and smtp debugging ([95a96c5](https://github.com/nextcloud/mail/commit/95a96c51ea77244bb9a946a9a91a554e4907ac8f))


### Performance Improvements

* don't show skeleton for cached mailboxes ([d5b5ae5](https://github.com/nextcloud/mail/commit/d5b5ae5eb0b05b41be6656edebc9707b616d5974))
* **imap:** avoid double login during mailbox sync ([221404a](https://github.com/nextcloud/mail/commit/221404a4108f09d3314535e3dcab80d046a01d7d))
* reuse a single imap client for the whole send chain ([01ea8be](https://github.com/nextcloud/mail/commit/01ea8bed7188145b713dbab198fc974f8f20876b))
* skip message skeleton if it is cached ([cd2603d](https://github.com/nextcloud/mail/commit/cd2603d7610c883785acca620fde2703668b0c8d))



# [5.1.0-dev.1](https://github.com/nextcloud/mail/compare/v4.2.0-beta4...v5.1.0-dev.1) (2025-03-26)


### Bug Fixes

* **db:** Do not JOIN recipients when fetching the latest messages ([b6e5255](https://github.com/nextcloud/mail/commit/b6e52559fa1fec5eee700cd4a53d57f7390f1538))
* **deps:** bump @iframe-resizer/child from 5.3.3 to ^5.4.2 (main) ([#10955](https://github.com/nextcloud/mail/issues/10955)) ([187a963](https://github.com/nextcloud/mail/commit/187a963720946c3ddfd734507efd1f9601c216c4))
* **deps:** bump @iframe-resizer/parent from 5.3.3 to ^5.4.3 (main) ([#10956](https://github.com/nextcloud/mail/issues/10956)) ([b9a7373](https://github.com/nextcloud/mail/commit/b9a73732f10d9bf883c006b00a9fc6c55c0b5780))
* **deps:** bump @nextcloud/cdav-library from 1.5.2 to ^1.5.3 (main) ([#10926](https://github.com/nextcloud/mail/issues/10926)) ([9d384ce](https://github.com/nextcloud/mail/commit/9d384ce3b9e665b1b9e3f0c4167758ed955c5905))
* **deps:** bump sabberworm/php-css-parser from 8.7.0 to ^8.8.0 (main) ([#10927](https://github.com/nextcloud/mail/issues/10927)) ([998aad8](https://github.com/nextcloud/mail/commit/998aad8167e5eeeb0f8bbaab8379d2a2318f9156))
* **dns:** Update public suffix list ([0d2f328](https://github.com/nextcloud/mail/commit/0d2f32877025dd52b16aa06e99f4494c90ce7d89))
* dont summerize empty messages ([24f44f4](https://github.com/nextcloud/mail/commit/24f44f48a8e0af3a26ad90cd038901da0ca8b9da))
* **imap:** Avoid OOM when syncing sparse mailboxes ([4d288d1](https://github.com/nextcloud/mail/commit/4d288d1096bc8cec4ff2cde38735d09b6eab922c))
* multiselect when you hold shift ([1e40683](https://github.com/nextcloud/mail/commit/1e40683ec09b02b0269d73fe2d0236691335b8cf))
* printing email threads and singular emails ([3b22ed5](https://github.com/nextcloud/mail/commit/3b22ed5752c0065c50643eb8591661f41b06df65))
* translation strings in printing feature ([#10944](https://github.com/nextcloud/mail/issues/10944)) ([b293310](https://github.com/nextcloud/mail/commit/b29331032977013e62cae510d531338922703037))


### Features

* **deps:** Add PHP8.4 support ([9ce0e6d](https://github.com/nextcloud/mail/commit/9ce0e6d5923ebbcf7dac6f2423d18f172b0d6bb8))
* **deps:** Add PHP8.4 support ([eb75307](https://github.com/nextcloud/mail/commit/eb753074fa5e6b8efd2bac2366fc3336e597984d))
* save the composer state per account ([b19b265](https://github.com/nextcloud/mail/commit/b19b2656a4e18871d77383fd438373adf4b0165d))



# [5.1.0-dev.1](https://github.com/nextcloud/mail/compare/v4.2.0-beta4...v5.1.0-dev.1) (2025-03-26)


### Bug Fixes

* add title for composer actions ([d8095ff](https://github.com/nextcloud/mail/commit/d8095ffbc1f4f9987a939b51001b53a0777e3cd7))
* Adjust TaskProcessingListener ([4ee353b](https://github.com/nextcloud/mail/commit/4ee353bea1476e2f9aeaacd5746dd7529efc0ca5))
* Adjust TaskProcessingListener Again ([f0758c8](https://github.com/nextcloud/mail/commit/f0758c8ee6c9c9813abcc8eb54f6d912063da868))
* allow to send attachment without setting disposition ([5fe5275](https://github.com/nextcloud/mail/commit/5fe5275c8b8b5ea648b39e346b0e1b88693e814b))
* apostrophe issue ([032a0bf](https://github.com/nextcloud/mail/commit/032a0bf02617df793542156a0c1ded805b61d08b))
* better help text when sieve is not enabled ([a7177b3](https://github.com/nextcloud/mail/commit/a7177b32fa882dcea0bb3ac2f015cfc8d8594073))
* change status code from 404 to 204 for missing avatars ([92bf4ef](https://github.com/nextcloud/mail/commit/92bf4ef422c3d4fcdde0e3538dd81e020b008346))
* **CKEditor:** show mentions ([974e964](https://github.com/nextcloud/mail/commit/974e96437796c6ba9f1c5c40886a2357f2b39f96))
* **db:** Allow long references ([3814786](https://github.com/nextcloud/mail/commit/38147868b63719c666ba6dea05b9f55deef91c46))
* **db:** Catch message IDs that are too long ([147d694](https://github.com/nextcloud/mail/commit/147d69436a56ee645880575cbaf0a458096d038c))
* decoding preview texts ([945b4a2](https://github.com/nextcloud/mail/commit/945b4a2b3156ce13b4dc8c7fd4f86a121146a6c1))
* deprecation warning ([9e6fd29](https://github.com/nextcloud/mail/commit/9e6fd2931d9c77e62658f2e491d963f663cfc622))
* **deps:** bump @nextcloud/files from 3.10.1 to ^3.10.2 (main) ([#10789](https://github.com/nextcloud/mail/issues/10789)) ([93f5fa1](https://github.com/nextcloud/mail/commit/93f5fa11823140870f6ce6b472d158a9b9628124))
* **deps:** bump @nextcloud/moment from 1.3.2 to ^1.3.2 (main) ([#10595](https://github.com/nextcloud/mail/issues/10595)) ([77cf0ed](https://github.com/nextcloud/mail/commit/77cf0ed65e1f9b8b350b0ea06bbf1b57e690b897))
* **deps:** bump @nextcloud/vue from 8.22.0 to ^8.23.1 ([7ab0d99](https://github.com/nextcloud/mail/commit/7ab0d996aee86ebb342cc0d32913191d59b11095))
* **deps:** bump @pinia/testing from 0.1.7 to ^0.1.7 ([354d684](https://github.com/nextcloud/mail/commit/354d684e2ae3411b577178c613ce3a7d6fe7e3ef))
* **deps:** bump address-rfc2822 from 2.2.2 to ^2.2.3 (main) ([#10597](https://github.com/nextcloud/mail/issues/10597)) ([d2d654d](https://github.com/nextcloud/mail/commit/d2d654d6dd18333610a3ba5e5f8df29d23c441e0))
* **deps:** bump bytestream/horde-imap-client from 2.33.3 to ^2.33.4 (main) ([#10598](https://github.com/nextcloud/mail/issues/10598)) ([54150fd](https://github.com/nextcloud/mail/commit/54150fd0df374a258c19b0193781f48e82bd056c))
* **deps:** bump bytestream/horde-imap-client from 2.33.4 to ^2.33.5 (main) ([#10828](https://github.com/nextcloud/mail/issues/10828)) ([984b3bf](https://github.com/nextcloud/mail/commit/984b3bf6d8e139f106c51840ab5489714ddf7a81))
* **deps:** Bump ckeditor from 38.1.1 t0 39.0.2 ([0aee4df](https://github.com/nextcloud/mail/commit/0aee4dfd312620a3a197fa0787379f4cfb4031dc))
* **deps:** bump ckeditor from v40 to v41 ([f7dac82](https://github.com/nextcloud/mail/commit/f7dac82d0dd6631d1f7f1ddabac0371e6a686b5a))
* **deps:** bump ckeditor v41 to v43 ([5482184](https://github.com/nextcloud/mail/commit/5482184cd6c77060c19dcfdf1b49134f53fabf7b))
* **deps:** bump ckeditor v43 to latest ([89d5a21](https://github.com/nextcloud/mail/commit/89d5a210ccdbe07dde8547cc766d5be42ae4547f))
* **deps:** bump core-js from 3.39.0 to ^3.40.0 (main) ([#10623](https://github.com/nextcloud/mail/issues/10623)) ([f8d8b04](https://github.com/nextcloud/mail/commit/f8d8b040b0bc70ed6c7e369fb6cb5d329ab20b28))
* **deps:** bump core-js from 3.40.0 to ^3.41.0 (main) ([#10819](https://github.com/nextcloud/mail/issues/10819)) ([e6bafb3](https://github.com/nextcloud/mail/commit/e6bafb371adac9e48f5443a812a91859b1125f99))
* **deps:** bump dompurify from 3.2.3 to ^3.2.3 (main) ([#10618](https://github.com/nextcloud/mail/issues/10618)) ([7f28b26](https://github.com/nextcloud/mail/commit/7f28b267f5727e92a754eeadec5302bca3a413b0))
* **deps:** bump dompurify from 3.2.3 to v3.2.4 (main) ([#10702](https://github.com/nextcloud/mail/issues/10702)) ([26f784f](https://github.com/nextcloud/mail/commit/26f784ff20ae107cdc5a29a310314ea4b2e96455))
* **deps:** bump dompurify from 3.2.4 to ^3.2.4 (main) ([#10790](https://github.com/nextcloud/mail/issues/10790)) ([58d636c](https://github.com/nextcloud/mail/commit/58d636c26f40ae77bf2bd99276dfd0167448199a))
* **deps:** bump pinia from 2.3.0 to ^2.3.1 (main) ([#10619](https://github.com/nextcloud/mail/issues/10619)) ([18c6354](https://github.com/nextcloud/mail/commit/18c6354b9214d9e74c028d911231c8ffac78ae85))
* **deps:** bump psr/log from 3.0.2 to ^3.0.2 (main) ([#10806](https://github.com/nextcloud/mail/issues/10806)) ([629cb59](https://github.com/nextcloud/mail/commit/629cb59f7204d21c3f6c1bcca139f844916695be))
* **deps:** bump stylelint from 16.11.0 to ^16.15.0 (main) ([#10820](https://github.com/nextcloud/mail/issues/10820)) ([e8371d3](https://github.com/nextcloud/mail/commit/e8371d3faa11966de99e3d0fb61096473163a72b))
* **deps:** bump stylelint from 16.15.0 to ^16.16.0 (main) ([#10908](https://github.com/nextcloud/mail/issues/10908)) ([9d66ff5](https://github.com/nextcloud/mail/commit/9d66ff53cd49521768f52e64545575188027b414))
* **deps:** Do not ship psr/log ([2f8f9bd](https://github.com/nextcloud/mail/commit/2f8f9bdbd9ec6c40fd8fe26e340da9279d74097f))
* detect imip messages from outlook.com ([b965355](https://github.com/nextcloud/mail/commit/b96535569a5ffdf59292f8032896f4e5e5662f4c))
* **dns:** Update public suffix list ([76cc21f](https://github.com/nextcloud/mail/commit/76cc21f94b2af56064341774f2612325dd171647))
* **dns:** Update public suffix list ([692512b](https://github.com/nextcloud/mail/commit/692512bf8f0994985c552c70e254dd301f947411))
* **dns:** Update public suffix list ([e981844](https://github.com/nextcloud/mail/commit/e981844ec685867fccb35fe2075e9794c40a9d67))
* go back warining discards the reply text ([72d6a53](https://github.com/nextcloud/mail/commit/72d6a532be1dd2852612a194801f2654257206de))
* handle 204 response ([0014941](https://github.com/nextcloud/mail/commit/0014941d48c199ced1d23bdca05fb09af4c6e572))
* handle utf-8 strings correctly in the link detection ([d219cee](https://github.com/nextcloud/mail/commit/d219cee47b6619e27300b8ce3238b834ea946e3b))
* harden phishing detection against missing and malformed headers ([f8338e6](https://github.com/nextcloud/mail/commit/f8338e6fa6520b5d13823af38c0be704d141627c))
* html5 errors ([fb4fae1](https://github.com/nextcloud/mail/commit/fb4fae1863ccea0aec70bd187748b02710f4bac0))
* **imap:** Sync mailboxes without a status ([2317686](https://github.com/nextcloud/mail/commit/2317686c91a4ec85d7e7b63914b89934cb176bf3))
* import for getTimezoneManager ([18589f7](https://github.com/nextcloud/mail/commit/18589f703ca24cbbd39d14abe972805b7d73bb1f))
* **mention:** Do not force SAB ([bad5a00](https://github.com/nextcloud/mail/commit/bad5a00ac570ec6af41bf531acf8a78adf96ede9))
* **message-summary:** respect admin config ([a7434c4](https://github.com/nextcloud/mail/commit/a7434c40ec59ef928c5baaebdd77cd51bcad0b6a))
* messagen content escaping message element ([8089ccb](https://github.com/nextcloud/mail/commit/8089ccb0f3915d57520458ecf0ce8729341cfbd4))
* migrate preferences to initial state ([0cfce5c](https://github.com/nextcloud/mail/commit/0cfce5ce991eb830d3953d870b957ac616d012cb))
* **migration:** make misc migrations idempotent ([d856a60](https://github.com/nextcloud/mail/commit/d856a6005c453e2cd91666941897aed3ca9cbf33))
* outbox message not being cleared after sending ([93a5c5a](https://github.com/nextcloud/mail/commit/93a5c5a1f7b5aa32a7269ff5f4a83fd0c5aa6ea2))
* **phishing:** Do not force the SAB ([78c2c4c](https://github.com/nextcloud/mail/commit/78c2c4cdcc08e1440a8c1b5e1073f99037ef2b4b))
* **phishing:** Uninitialized string offset error ([fdbbc62](https://github.com/nextcloud/mail/commit/fdbbc623637f94a326f1b90166f54be8d176d792))
* recipient label should not contain email address ([3a0ab5a](https://github.com/nextcloud/mail/commit/3a0ab5a1f131a4ae039dd43da84793f8edfff9b1))
* reloading recipeint info when you expand and collapse the composer ([9b213ec](https://github.com/nextcloud/mail/commit/9b213ecb7a85ec22856faf345bbd582bca43c5e3))
* remove photo property from groups integration ([66ddbd3](https://github.com/nextcloud/mail/commit/66ddbd32d9deb88d46f8e7afbd9949107ed74f06))
* reset filter does not work ([f0e847b](https://github.com/nextcloud/mail/commit/f0e847bb47d993ce1c7cfa2b00898da14aac36fb))
* shorten ai summaries to fit in message list better ([b28777c](https://github.com/nextcloud/mail/commit/b28777c4d49abbcec62e8a5b4fbe0c2170c51e8c))
* show the email address as subname ([0b50b3a](https://github.com/nextcloud/mail/commit/0b50b3abde5f600e905d9386c4a1b4c091e95409))
* **summarizeMessages:** use TextToText ([40ab10f](https://github.com/nextcloud/mail/commit/40ab10fc71eeadc30f0c3d2fcc3f6fd18d9551d9))
* **Thread:** add error message for emails not able to be opened ([691c33e](https://github.com/nextcloud/mail/commit/691c33e687fced93cda00dc926c2dbb316385990))
* throwing errors in ai intergration frontend service ([d8e074c](https://github.com/nextcloud/mail/commit/d8e074c75a43f41867a9ed4595a59883a60a7ff8))
* user must scroll to view most recent message in thread ([23a544b](https://github.com/nextcloud/mail/commit/23a544bc60c4d020a783ea303f0703985e913414))


### Features

* add warning when the message has no subject ([7eea2ac](https://github.com/nextcloud/mail/commit/7eea2ac3fce6da478dceef706148ce361dd0724c))
* check connection performance of mail service ([800e964](https://github.com/nextcloud/mail/commit/800e964c110e38411c1d7478f43abec2b54934c6))
* **deps:** Add Nextcloud 32 support ([2c026a0](https://github.com/nextcloud/mail/commit/2c026a0af96e9645198caa356b790926becfffb2))
* **ocs:** list accounts and aliases of current user ([8122306](https://github.com/nextcloud/mail/commit/812230641b07d8de68985e222a7c0e2e6b726c7d))
* save the composer state per account ([b19b265](https://github.com/nextcloud/mail/commit/b19b2656a4e18871d77383fd438373adf4b0165d))
* setup check for mail transport php-mail ([d24a009](https://github.com/nextcloud/mail/commit/d24a0090a3571961b2029a99508c13c3980afae1))



# [4.2.0-beta3](https://github.com/nextcloud/mail/compare/v4.2.0-beta1...v4.2.0-beta3) (2025-01-16)


### Bug Fixes

* add ability to send alternate text (html and plain) ([3165f07](https://github.com/nextcloud/mail/commit/3165f07ffbb8fcd7f1d6feeacede0530641ce6b0))
* **deps:** bump @nextcloud/files from 3.10.1 to ^3.10.1 (main) ([#10566](https://github.com/nextcloud/mail/issues/10566)) ([44b0c76](https://github.com/nextcloud/mail/commit/44b0c765bd5678d71d22aa5ee4b7f7c4d78fc5bc))
* **deps:** bump @nextcloud/l10n from 2.2.0 to v3 ([280b73b](https://github.com/nextcloud/mail/commit/280b73bedd386092b09743067925506d06e3b5e1))
* **deps:** bump @nextcloud/logger from 2.7.0 to v3 ([b72df2e](https://github.com/nextcloud/mail/commit/b72df2ec322eca7dbd58676be2c5b32035ab0b0d))
* **dns:** Update public suffix list ([63f9e66](https://github.com/nextcloud/mail/commit/63f9e66a9878f28ed337e04a64aae12bbc68c782))
* **translationService:** correct API reading ([19baf3e](https://github.com/nextcloud/mail/commit/19baf3e8b6191a4d39e1cf21f79c8718a5a025c9))



# [4.2.0-beta1](https://github.com/nextcloud/mail/compare/v4.2.0-alpha2...v4.2.0-beta1) (2025-01-14)


### Bug Fixes

* **deps:** Apply npm audit fix ([879476b](https://github.com/nextcloud/mail/commit/879476b228b6ca8830e4715f7afd04a294cf7006))
* **deps:** Apply npm audit fix ([f4eaffb](https://github.com/nextcloud/mail/commit/f4eaffbfde5ebfe6724cfe8498cb157de126ce41))
* **deps:** bump @nextcloud/vue from 8.22.0 to ^8.22.0 ([1c577e4](https://github.com/nextcloud/mail/commit/1c577e48eacc2206ea8cee427b9336fe2ea4e1b2))
* empty content position ([bd9de95](https://github.com/nextcloud/mail/commit/bd9de953253505de1a13ec4493f7211752e98dc4))
* Restrict allowed JSON response HTTP codes ([5620710](https://github.com/nextcloud/mail/commit/56207107a75947be74188779c1f6bd0bbd5958d9))


### Features

* Add translation capabilities to mail ([638ecc5](https://github.com/nextcloud/mail/commit/638ecc5df4a7d63a7b76e0abd1721a895bd80146))


### Performance Improvements

* **ui:** Load avatar URLs with low priority ([23551cf](https://github.com/nextcloud/mail/commit/23551cf8a8bd9a4986987c13e4903793bb63e2b7))



# [4.2.0-alpha1](https://github.com/nextcloud/mail/compare/v4.0.0-beta2...v4.2.0-alpha1) (2025-01-03)


### Bug Fixes

* accept avatars with image/vnd.microsoft.icon mime types ([d6cf286](https://github.com/nextcloud/mail/commit/d6cf286531011d087344e5586c52be98a575fd73))
* **AccountForm:** RTL support ([c4dd175](https://github.com/nextcloud/mail/commit/c4dd175f77cdaa6c276d1a67badceb4f1698ed38))
* add null check for sender in imipservice ([2885f62](https://github.com/nextcloud/mail/commit/2885f62f36773d090d839368cc1ed28a92c122e7))
* **avatar:** Use Nextcloud HTTP client for favicons ([eee2d6a](https://github.com/nextcloud/mail/commit/eee2d6a23bc65a8d687c8eb5eba8f59688492fb9))
* Center envelope header avatar ([1324d1e](https://github.com/nextcloud/mail/commit/1324d1e98fbd1d3e697f511fc027dc4631f96301))
* CKEditor translations ([22415b0](https://github.com/nextcloud/mail/commit/22415b020db15084db5c6f51c72e0e617f851e5f))
* **Composer:** rtl support ([e16a6e1](https://github.com/nextcloud/mail/commit/e16a6e11749db28db11f4e04237adabea951891f))
* **contactsintegration:** Limit number of matches ([ada36b8](https://github.com/nextcloud/mail/commit/ada36b8e804e51d39ff439965e579f949c4f6fd8))
* **contactsintegration:** Limit number of search results ([b02921e](https://github.com/nextcloud/mail/commit/b02921e5ec724cb3de0b50e520ca780be703c42e))
* create mailbox doesnt reset after saving ([e7da6c7](https://github.com/nextcloud/mail/commit/e7da6c7f9ff3b6f7cc89531e1595e7ebfdb91481))
* **deps:** Apply npm audit fix ([91bbac9](https://github.com/nextcloud/mail/commit/91bbac944db78776bc98b2b28c8b35fb0e382b00))
* **deps:** Apply npm audit fix ([a77c8e0](https://github.com/nextcloud/mail/commit/a77c8e0a7ee25c64e97ae71383472a757476ad14))
* **deps:** Apply npm audit fix ([40c782b](https://github.com/nextcloud/mail/commit/40c782bf3114e4e3f4ca0b0ddce766ff90aebc9a))
* **deps:** Apply npm audit fix ([dcd3c37](https://github.com/nextcloud/mail/commit/dcd3c3725e01c1353a4e8c31f4bd2410ab3452d5))
* **deps:** Apply npm audit fix ([1c67e7d](https://github.com/nextcloud/mail/commit/1c67e7d93387089666bdc4cbc98ec5f391a12925))
* **deps:** Apply npm audit fix ([84a6de9](https://github.com/nextcloud/mail/commit/84a6de9472a62db5200fb5e46c337a8a29840f87))
* **deps:** bump @nextcloud/auth from 2.3.0 to ^2.4.0 ([8a8f6d4](https://github.com/nextcloud/mail/commit/8a8f6d4b9e55798caa9d44f497c231ba6b3afa04))
* **deps:** bump @nextcloud/axios from 2.5.0 to ^2.5.1 (main) ([#10212](https://github.com/nextcloud/mail/issues/10212)) ([cfbda53](https://github.com/nextcloud/mail/commit/cfbda53f7c71dd799c5e0bae8ff654d181278e95))
* **deps:** bump @nextcloud/cdav-library from 1.5.1 to ^1.5.2 (main) ([#10265](https://github.com/nextcloud/mail/issues/10265)) ([e3ad554](https://github.com/nextcloud/mail/commit/e3ad55408648f12843a44098363d28205020c156))
* **deps:** bump @nextcloud/dialogs from 5.3.6 to ^5.3.7 (main) ([#10061](https://github.com/nextcloud/mail/issues/10061)) ([c1965b1](https://github.com/nextcloud/mail/commit/c1965b19e1b2106221ff19e9fd480183297a9412))
* **deps:** bump @nextcloud/dialogs from 5.3.8 to ^5.3.8 (main) ([#10354](https://github.com/nextcloud/mail/issues/10354)) ([066dda1](https://github.com/nextcloud/mail/commit/066dda15dbe2c50ea25682b860221598ecae6658))
* **deps:** bump @nextcloud/files from 3.9.0 to ^3.9.0 (main) ([#10213](https://github.com/nextcloud/mail/issues/10213)) ([42816ed](https://github.com/nextcloud/mail/commit/42816ed37c4475856e137c435446cfd3d11a9978))
* **deps:** bump @nextcloud/files from 3.9.1 to ^3.10.0 (main) ([#10375](https://github.com/nextcloud/mail/issues/10375)) ([30a9617](https://github.com/nextcloud/mail/commit/30a96176b5160e8b00c645bc2ec05f438548c83b))
* **deps:** bump @nextcloud/files from 3.9.1 to ^3.9.1 (main) ([#10303](https://github.com/nextcloud/mail/issues/10303)) ([a03b721](https://github.com/nextcloud/mail/commit/a03b72138cf7c02b08e4a31771a19da5aa10e4e9))
* **deps:** bump @nextcloud/vue from 8.16.0 to ^8.17.1 ([397ed27](https://github.com/nextcloud/mail/commit/397ed272d89eb04ec08e2c59476c59e35744fb8a))
* **deps:** bump @nextcloud/vue from 8.18.0 to ^8.19.0 ([cf62083](https://github.com/nextcloud/mail/commit/cf6208310c94b5171f274a2746b6922044ed28ec))
* **deps:** bump @nextcloud/vue from 8.19.0 to ^8.20.0 ([b52df89](https://github.com/nextcloud/mail/commit/b52df895ef03086cca566adc042c777e9340d28d))
* **deps:** bump arthurhoaro/favicon from 2.0.0 to ^2.0.1 (main) ([#10304](https://github.com/nextcloud/mail/issues/10304)) ([a8976cc](https://github.com/nextcloud/mail/commit/a8976cc8f01032c9a8cc2c2c01b613d758794979))
* **deps:** bump bytestream/horde-imap-client from 2.33.2 to ^2.33.3 (main) ([#10335](https://github.com/nextcloud/mail/issues/10335)) ([9c43e51](https://github.com/nextcloud/mail/commit/9c43e5195f9278fe05513559c3f37cd7229f638b))
* **deps:** bump bytestream/horde-stream from 1.7.1 to ^1.7.2 (main) ([#10336](https://github.com/nextcloud/mail/issues/10336)) ([72ff480](https://github.com/nextcloud/mail/commit/72ff4801669708fef3d9155e8ed75892cfd9bfdd))
* **deps:** bump bytestream/horde-util from 2.7.0 to ^2.8.0 (main) ([#10266](https://github.com/nextcloud/mail/issues/10266)) ([dea3c67](https://github.com/nextcloud/mail/commit/dea3c677e130098b7fbb8a90b6ed03d2f822f0a3))
* **deps:** bump cerdic/css-tidy from 2.1.0 to v2.2.1 (main) ([#10401](https://github.com/nextcloud/mail/issues/10401)) ([6168da8](https://github.com/nextcloud/mail/commit/6168da8c3c2ff64690242a3efdba0e585967214f))
* **deps:** bump dompurify from 3.1.6 to ^3.1.7 (main) ([#10239](https://github.com/nextcloud/mail/issues/10239)) ([b6f3726](https://github.com/nextcloud/mail/commit/b6f3726157f7e07d4c05c72bbf52af8f68a66c1b))
* **deps:** bump dompurify from 3.1.7 to ^3.2.1 (main) ([#10402](https://github.com/nextcloud/mail/issues/10402)) ([4ef5e62](https://github.com/nextcloud/mail/commit/4ef5e624374082cb05d8a409227bc366c3b34692))
* **deps:** bump ezyang/htmlpurifier from 4.17.0 to v4.18.0 (main) ([#10414](https://github.com/nextcloud/mail/issues/10414)) ([d7afc16](https://github.com/nextcloud/mail/commit/d7afc16198b2c61a08b692f302fb9ccc71cd37fe))
* **deps:** bump jeremykendall/php-domain-parser from 6.3.0 to ^6.3.1 (main) ([#10372](https://github.com/nextcloud/mail/issues/10372)) ([5734a9e](https://github.com/nextcloud/mail/commit/5734a9e466852ce8bd0f57bf9d2bedad09e61f96))
* **deps:** bump nextcloud/openapi-extractor from 1.0.0 to ^1.0.1 (main) ([#10256](https://github.com/nextcloud/mail/issues/10256)) ([2bd6c4f](https://github.com/nextcloud/mail/commit/2bd6c4fa8714059f856e252f300de4142c4f74a4))
* **deps:** bump pinia from 2.2.0 to ^2.2.3 (main) ([#10028](https://github.com/nextcloud/mail/issues/10028)) ([5f851f1](https://github.com/nextcloud/mail/commit/5f851f1ab0401b1d5904ff976838586684513704))
* **deps:** bump pinia from 2.2.3 to ^2.2.4 ([0439f04](https://github.com/nextcloud/mail/commit/0439f046eea92be97f67310a33b8dbce45d2d47b))
* **deps:** bump pinia from 2.2.4 to ^2.2.5 (main) ([#10305](https://github.com/nextcloud/mail/issues/10305)) ([5084d6e](https://github.com/nextcloud/mail/commit/5084d6ef5356e8d13016b20c5ecaf594b269a8b3))
* **deps:** bump pinia from 2.2.5 to ^2.2.6 (main) ([#10373](https://github.com/nextcloud/mail/issues/10373)) ([828b5cb](https://github.com/nextcloud/mail/commit/828b5cbd960de0d7dbc0a4f8e248a8c0428b3462))
* **deps:** bump rubix/ml from 2.5.0 to v2.5.1 (main) ([#10096](https://github.com/nextcloud/mail/issues/10096)) ([45dc3f4](https://github.com/nextcloud/mail/commit/45dc3f41c163574584f36166f17ca76a7ae7b926))
* **deps:** bump rubix/ml from 2.5.1 to v2.5.2 (main) ([#10374](https://github.com/nextcloud/mail/issues/10374)) ([62a0f24](https://github.com/nextcloud/mail/commit/62a0f24a80f9a21e4655e95ce148f76662e75dd4))
* **deps:** bump sabberworm/php-css-parser from 8.6.0 to ^8.7.0 (main) ([#10306](https://github.com/nextcloud/mail/issues/10306)) ([15b9cba](https://github.com/nextcloud/mail/commit/15b9cbab6f1749fc2b5c00502258259672cddd53))
* **deps:** bump stylelint from 16.3.1 to ^16.10.0 (main) ([#10284](https://github.com/nextcloud/mail/issues/10284)) ([b895441](https://github.com/nextcloud/mail/commit/b89544189022c8b49b10df3d25a37a44a9fad620))
* **deps:** bump vue-material-design-icons from 5.3.0 to ^5.3.1 ([371ce44](https://github.com/nextcloud/mail/commit/371ce44ac6e21fe99e64ca740074d76f155249e9))
* **deps:** bump webdav from 4.11.3 to v4.11.4 (main) ([#10127](https://github.com/nextcloud/mail/issues/10127)) ([129b066](https://github.com/nextcloud/mail/commit/129b066e69852e5d6da294d2e705024d9403a5b6))
* **deps:** bump webpack from 5.91.0 to v5.94.0 (main) ([#10062](https://github.com/nextcloud/mail/issues/10062)) ([f66b05a](https://github.com/nextcloud/mail/commit/f66b05ac2f9d0c459c904ad32c3b72fc23ddc16e))
* **dns:** Update public suffix list ([547b906](https://github.com/nextcloud/mail/commit/547b9064c3b65c5a5725a94a618fcd919dc361aa))
* **dns:** Update public suffix list ([2e7ff02](https://github.com/nextcloud/mail/commit/2e7ff02bff6747d9054743d822b8c5e516123b94))
* **dns:** Update public suffix list ([939830c](https://github.com/nextcloud/mail/commit/939830ce13842ce4e568b12400697be31429d82a))
* **dns:** Update public suffix list ([70ecb76](https://github.com/nextcloud/mail/commit/70ecb760ebcc28feac135575cdf6a0afc284dc1c))
* **dns:** Update public suffix list ([9e14c38](https://github.com/nextcloud/mail/commit/9e14c38d45d880e8f1f9cd186660736235799f49))
* Encapsulate PGP/MIME encrypted emails ([3f821e3](https://github.com/nextcloud/mail/commit/3f821e38e37cda3c1ae165a141f1b38f62191fbe))
* **Envelope:** RTL support ([8cd27ad](https://github.com/nextcloud/mail/commit/8cd27ad1ac2e41ef919733ff87a4439f776d7e69))
* fetch attendance status when calendars are loaded ([44b9a36](https://github.com/nextcloud/mail/commit/44b9a36eea9e4e77dee04883b561804b9ca66016)), closes [/github.com/nextcloud/mail/blob/6fc45eb0630b9065f9ccb4c1da5cc9557f7df834/src/App.vue#L49-L50](https://github.com//github.com/nextcloud/mail/blob/6fc45eb0630b9065f9ccb4c1da5cc9557f7df834/src/App.vue/issues/L49-L50)
* fix renaming mailbox hierarchy ([fd6229a](https://github.com/nextcloud/mail/commit/fd6229a8c1291dda099977b2bc98f2ec37e6bd2d))
* Hand PGP/MIME-encrypted emails to Mailvelope ([971f910](https://github.com/nextcloud/mail/commit/971f9104ebb5a8434756149f317a938070af98bc))
* hide show links button when not needed ([9351f0f](https://github.com/nextcloud/mail/commit/9351f0fa31cea520be18762beeacf76ace814a46))
* **iframe:** scroll horizontally in case of overflow ([ee521d4](https://github.com/nextcloud/mail/commit/ee521d46ed0382d27882c302bf208038ffc445fc))
* **imap:** Consider charset for preview text decoding ([625c579](https://github.com/nextcloud/mail/commit/625c579c82a06f2afaa21e130a4fd9f6c99c20ed))
* **imap:** do a single full sync when QRESYNC is enabled ([73ceaf3](https://github.com/nextcloud/mail/commit/73ceaf3037d08f496e864183e0898983a9becf91))
* **imap:** persist vanished messages immediately on EXAMINE commands ([3b577fe](https://github.com/nextcloud/mail/commit/3b577feb4d750fb2811e7488354d0663963c9840))
* increase default SMTP timeout ([e7e62a3](https://github.com/nextcloud/mail/commit/e7e62a38c8bd15bc52b31a04c6b4595df5ec3cc4))
* input icons and native datepicker ([db36047](https://github.com/nextcloud/mail/commit/db360471eee40465868be331c00858dfd280e08e))
* Junk/NotJunk flags ([fb27d1e](https://github.com/nextcloud/mail/commit/fb27d1e9e3d00a0f4463bb08310933740f67e6d6))
* link checking missing scheme ([9bb48cd](https://github.com/nextcloud/mail/commit/9bb48cda5bb74a1552d615ba57e0a4f29b96224b))
* mailbox loading icon position ([16e95a1](https://github.com/nextcloud/mail/commit/16e95a13ee02546c329850370aa3e85b75499bbf))
* **mailto:** Handle BCC recipients only ([970c3ac](https://github.com/nextcloud/mail/commit/970c3ac519c3659174c82ce5c46494b1354f6881))
* make filter routes available for normal users ([95984e8](https://github.com/nextcloud/mail/commit/95984e88d8f5c87a06eaee93fc2ada63128ebf57))
* new message button misalignment ([94ba7d3](https://github.com/nextcloud/mail/commit/94ba7d3015415743c9a13d14b7504f4ca0e55112))
* **notifications:** Notifier::prepare() threw \InvalidArgumentException which is deprecated ([37a2365](https://github.com/nextcloud/mail/commit/37a2365a9e008c2f838d419ffe0cf57ab70628d4))
* open search buttons on focus and fix the wraping ([b469ce3](https://github.com/nextcloud/mail/commit/b469ce35206b82ea71d9d5b57a9cf6dedc5cf32b))
* overlapping text on small screens ([04b8849](https://github.com/nextcloud/mail/commit/04b8849ad9caaf6830c61349098fedcb33f7fa0d))
* phishing detection fixes ([dba8aff](https://github.com/nextcloud/mail/commit/dba8aff42fa07cea8412975c2b834c0482e2a9c2))
* phishing warning layout ([2867a68](https://github.com/nextcloud/mail/commit/2867a68dbde60f379a2705c6756a09697924539f))
* **PhishingDetection:** empty href ([ff608cc](https://github.com/nextcloud/mail/commit/ff608cc973798678e1b94c746958d9f17e22267c))
* **psrlog:** Make the logger compatible with the upcoming bump to psr/log 3 in server ([0a22b3f](https://github.com/nextcloud/mail/commit/0a22b3f174056c7f2b2ebc3eb3e2acfc484cbe73))
* **quick-search:** RTL support ([05e9ec7](https://github.com/nextcloud/mail/commit/05e9ec7af1763d3e4ffe505128ed92292e16184f))
* recipient popover on thread display ([1244a69](https://github.com/nextcloud/mail/commit/1244a691c4c2a49efe833cfef8f54075cd747a4b))
* **release:** Ignore unnecessary files ([0a66bdc](https://github.com/nextcloud/mail/commit/0a66bdcddf9182f0b8f033a6cf95a9dbfb6fa064))
* **release:** Specify release committer ([9f4d57a](https://github.com/nextcloud/mail/commit/9f4d57aea1144bb912fddf4eeffabebe13576412))
* remove depricated multiselect class ([31b11e5](https://github.com/nextcloud/mail/commit/31b11e5afd5c36758e89e7cb163b325cd42e2a53))
* Replace tune icon with filter icon ([71af3f8](https://github.com/nextcloud/mail/commit/71af3f8fb6be790de082eb29c926f6941381da65))
* Revive reply to sender only ([90c9298](https://github.com/nextcloud/mail/commit/90c9298550f0830b70daba05b0f334b14ec00d78))
* **rtl:** phishing warning component ([6516398](https://github.com/nextcloud/mail/commit/6516398e9a2f7d12584cacbe63dea68f4ebc9983))
* **rtl:** Thread title ([62583f8](https://github.com/nextcloud/mail/commit/62583f8fa1bec2a7b072c8c705880bc8d378b7b2))
* **rtl:** userBubble rtl support ([e3b6729](https://github.com/nextcloud/mail/commit/e3b6729ae90b4e879a57dc51ceca76521f3c4f20))
* search modal on small screen ([a3afdd4](https://github.com/nextcloud/mail/commit/a3afdd411c525e41e947ddac86e2146613f5136a))
* select multiple envelopes by holding shift directly ([80f7153](https://github.com/nextcloud/mail/commit/80f71536fdc7ad33c15585bf218c8aa562be5065))
* set content type parameters for attachments ([e3524c8](https://github.com/nextcloud/mail/commit/e3524c810ad82d7a5774bcda9ee4259ab8782a0e))
* thread disappearing after refresh ([fe12a29](https://github.com/nextcloud/mail/commit/fe12a29439c2b3c376819b27d44a034ad8cc2fb6))
* **threading:** Handle threads with duplicate send times ([341b655](https://github.com/nextcloud/mail/commit/341b655467ac672ec2463769ca3df284801f9a87))
* **ui:** Add padding to primary evenlope actions ([63f31a2](https://github.com/nextcloud/mail/commit/63f31a2009e3c0a4aef9e56b9a471ddcaf40ea26))
* **ui:** Remove padding from recipient bubble ([6026b1c](https://github.com/nextcloud/mail/commit/6026b1cc2111858b61077562f8ffe347f13eca8d))
* **ui:** Restore account quota fetching ([6e58a2d](https://github.com/nextcloud/mail/commit/6e58a2d4d13ba1e500cf1048c9295cfe70baae8d))
* **ui:** Restore message/thread styling ([1b94a14](https://github.com/nextcloud/mail/commit/1b94a14ce26608456560bec4955a3dc70852255e))
* use single connection to sync all mailboxes ([57389ba](https://github.com/nextcloud/mail/commit/57389ba85c8842351302a51e963629ed89120d0f))
* wrong path for itinerary executable ([43ed1ca](https://github.com/nextcloud/mail/commit/43ed1ca4f418806a768ef0df1cb9dc9b32170c36))


### Features

* add iMip Request Handling ([e8578ed](https://github.com/nextcloud/mail/commit/e8578edef275e434a2e2318808d0275d25fad378))
* add mention to mail ([f089fad](https://github.com/nextcloud/mail/commit/f089fad6aa59b5927c9f4a96005a22711ae47c8d))
* add recipient info on the right side of the composer ([efb0d60](https://github.com/nextcloud/mail/commit/efb0d605f2387ff426686eeb6339e79a3eeec91e))
* add sieve utils ([9486987](https://github.com/nextcloud/mail/commit/948698742810bc3802340ff94ba55ec28e2583cb))
* ai message summary ([e9286d2](https://github.com/nextcloud/mail/commit/e9286d20d4803cd1d4a3b7635f9c1dcf6fe4dcec))
* classify emails by importance based on subjects ([1907ebc](https://github.com/nextcloud/mail/commit/1907ebc860f49de046640e00b523999a15028e66))
* **deps:** Add Nextcloud 31 support ([bd306d8](https://github.com/nextcloud/mail/commit/bd306d8db5edf661c10b69eaf28ce095634ffab4))
* fiter messages by mention ([fb946a2](https://github.com/nextcloud/mail/commit/fb946a2da61db01a6ab197d523fa95dd7dc6e57b))
* implement periodic full sync job to repair cache inconsistencies ([c0bed86](https://github.com/nextcloud/mail/commit/c0bed86544f5a62fc3924839da669cfe5a6251c1))
* mail filters ([d74c401](https://github.com/nextcloud/mail/commit/d74c401e6653be7d0713cb0c6d2ab358c9cf7516))
* mail provider backend ([ce653d0](https://github.com/nextcloud/mail/commit/ce653d0f49fddc2afe916e5972e0e4642fed4ef6))
* make multiselect menu more discoverable ([085af79](https://github.com/nextcloud/mail/commit/085af794d731d8585098e9c1fc620235a3d161f1))


### Performance Improvements

* don't loop the users without any provisioning configurations ([b76e68f](https://github.com/nextcloud/mail/commit/b76e68f37352f93dd28bc70c2addf1b9f75ee1af))
* skip non-writable calendars ([4296585](https://github.com/nextcloud/mail/commit/42965856b0761038f699ea7e3092498336a7e439))


### Reverts

* Revert "fix: CKEditor translations" ([5a000da](https://github.com/nextcloud/mail/commit/5a000da5fc064bbbf132ad59fd13a2003cba9352))
* Revert "perf: bundle with vite" ([6539d1d](https://github.com/nextcloud/mail/commit/6539d1da520a1531a3febeb27397527db8adb9a1))



# [4.0.0-beta2](https://github.com/nextcloud/mail/compare/v4.0.0-alpha1...v4.0.0-beta2) (2024-08-27)


### Bug Fixes

* **.nextcloudignore:** Exclude php-stemmer tests from package ([aa79c29](https://github.com/nextcloud/mail/commit/aa79c2924e68893d3dbaae9deeb2b6a627d3c0ce)), closes [#9586](https://github.com/nextcloud/mail/issues/9586)
* account deletion modal design ([091960a](https://github.com/nextcloud/mail/commit/091960ae1049393d7b9e538cf657b1068e4c6243))
* **AccountForm:** Fix manual mail server buttons ([4d37cdc](https://github.com/nextcloud/mail/commit/4d37cdc0b746a434af41aa1aa999769c1e7f3bb2))
* add repair job to deleted duplicated cached messages ([22b683c](https://github.com/nextcloud/mail/commit/22b683c0ad1abadef0c7dc267c973caa337b5e03))
* align reply and attachment icon with subject ([8965b73](https://github.com/nextcloud/mail/commit/8965b739d76a27907b78b3632d3b000fc50fa87e))
* **autoconfig:** Refactor DNS query for testing ([56faa48](https://github.com/nextcloud/mail/commit/56faa48b26c68d2a93bee0a8befa715047f60d6e))
* **autoresponder:** enable immediately on an OutOfOfficeStartedEvent ([783263d](https://github.com/nextcloud/mail/commit/783263d2e91016f977db813ac5b5a965c6b88526))
* background-color of outbox-button ([ca8310b](https://github.com/nextcloud/mail/commit/ca8310b6d1479a3009197b87e73bcb24ff10932a))
* background-color of outbox-button ([5131682](https://github.com/nextcloud/mail/commit/51316823d2e6bffcf5f4c2b629d6243c9865d942))
* case insesitive comparison for contact emails ([30d55f7](https://github.com/nextcloud/mail/commit/30d55f79a96519d281badcebd992ce691ad7e16d))
* change appnavigation to appnavigationcaption for the email account ([4ffc09b](https://github.com/nextcloud/mail/commit/4ffc09b450787c882c9ca341798000e39b751215))
* change format button icon ([9700cda](https://github.com/nextcloud/mail/commit/9700cda2fafa6bae5170176c01da260653cf5412))
* Check if mailbox folder is selectable ([be8dcf4](https://github.com/nextcloud/mail/commit/be8dcf48e494c2594ca1a6ce2bdc599b977c3c84))
* close smtp connection after sending or on error ([8f9a89b](https://github.com/nextcloud/mail/commit/8f9a89bfad9c5dedcb129892ca95bafd2594b0f9))
* close the ncselect dropdown when clicked somewhere else ([d6daaef](https://github.com/nextcloud/mail/commit/d6daaef63b474fede424188f60ada7f3dad41be8))
* composer session indicator height ([cd9a9f2](https://github.com/nextcloud/mail/commit/cd9a9f236b05b7b5022cf180b5dd7dee040eb420))
* **composer:** Adjust expand/collapse cc/bcc icon size ([95803ba](https://github.com/nextcloud/mail/commit/95803ba920e60f87520dbf64f1fec60acf3091bb))
* **composer:** Prevent leaving the tab with unsaved changes ([e57db7b](https://github.com/nextcloud/mail/commit/e57db7b01bd7b2bfd793ccb58fd464300cf8fd9d))
* **composer:** Remove to/cc/bcc/subject separators ([a7983c5](https://github.com/nextcloud/mail/commit/a7983c5df04d48da90885e47e874c656169a1baf))
* **composer:** Revive ckeditor translations ([c14cb78](https://github.com/nextcloud/mail/commit/c14cb784cfc7b3aee1de7ea30a4148652af87052))
* create tasks from emails ([facc5bd](https://github.com/nextcloud/mail/commit/facc5bd581ebc344d0bb58f2ba6462b49ed16b15))
* **dashboard:** Fix dashboard icon ([8a2b586](https://github.com/nextcloud/mail/commit/8a2b58615a1a10a123041232e4b9dd2018369bb4))
* **db:** Delete recipients without sub query ([2545318](https://github.com/nextcloud/mail/commit/2545318697ee34c6a8112eeea7b1580af71e0568))
* declare all properties in unit tests ([3955d51](https://github.com/nextcloud/mail/commit/3955d514f5b795c86ab1bb2866ba061f9382bc07))
* Define "isAddAttachmentsOpen" ([ac97a28](https://github.com/nextcloud/mail/commit/ac97a284435299d5025d1d39b5151bb273cce27e))
* **deps:** Apply npm audit fix ([9e53bbb](https://github.com/nextcloud/mail/commit/9e53bbb908da2edcbd39d3ac188794079ae86117))
* **deps:** Apply npm audit fix ([7c2a1c1](https://github.com/nextcloud/mail/commit/7c2a1c1f7e2c9815eabd8ddb46d19d0c81f6e872))
* **deps:** Apply npm audit fix ([7278f5b](https://github.com/nextcloud/mail/commit/7278f5b477df2b848d1d04922f357ca24f88552e))
* **deps:** Apply npm audit fix ([2b0aa94](https://github.com/nextcloud/mail/commit/2b0aa94da03982a33cb0e0e55dccbe7f6d8a7ece))
* **deps:** Apply npm audit fix ([7bb7859](https://github.com/nextcloud/mail/commit/7bb78591fef8ccb345c18b02838b4ceaba9f83af))
* **deps:** bump @ckeditor/ckeditor5-editor-decoupled from 37.0.1 to v37.1.0 (main) ([#9480](https://github.com/nextcloud/mail/issues/9480)) ([1a63205](https://github.com/nextcloud/mail/commit/1a632052ff6c545c43274a6a7b4088b80b8b4614))
* **deps:** bump @nextcloud/auth from 2.2.1 to ^2.3.0 (main) ([#9630](https://github.com/nextcloud/mail/issues/9630)) ([b9cecc4](https://github.com/nextcloud/mail/commit/b9cecc43ce7e0498939342edf183389c55923007))
* **deps:** bump @nextcloud/axios from 2.5.0 to ^2.5.0 (main) ([#9631](https://github.com/nextcloud/mail/issues/9631)) ([8edd1d7](https://github.com/nextcloud/mail/commit/8edd1d7367e267e77fd7af4434dec734140b3b67))
* **deps:** bump @nextcloud/cdav-library from 1.3.0 to ^1.4.0 (main) ([#9777](https://github.com/nextcloud/mail/issues/9777)) ([309a4af](https://github.com/nextcloud/mail/commit/309a4aff5af910a4be790cc07459be29d3d995bf))
* **deps:** bump @nextcloud/cdav-library from 1.4.0 to ^1.5.0 (main) ([#9873](https://github.com/nextcloud/mail/issues/9873)) ([45de8bf](https://github.com/nextcloud/mail/commit/45de8bf43f4e3e54da1669da41875d653eabe897))
* **deps:** bump @nextcloud/cdav-library from 1.5.0 to ^1.5.1 (main) ([#9906](https://github.com/nextcloud/mail/issues/9906)) ([97cd5f4](https://github.com/nextcloud/mail/commit/97cd5f49351036e653537d3b53c69ecb620d7db1))
* **deps:** bump @nextcloud/dialogs from 5.2.0 to ^5.3.0 (main) ([#9572](https://github.com/nextcloud/mail/issues/9572)) ([9da3f56](https://github.com/nextcloud/mail/commit/9da3f5612965f343fcffa60fc592340bad572cde))
* **deps:** bump @nextcloud/dialogs from 5.3.0 to ^5.3.1 (main) ([#9595](https://github.com/nextcloud/mail/issues/9595)) ([e2ce479](https://github.com/nextcloud/mail/commit/e2ce479480d71a2d6abfadcaa14007098c7e5554))
* **deps:** bump @nextcloud/dialogs from 5.3.1 to ^5.3.3 (main) ([#9732](https://github.com/nextcloud/mail/issues/9732)) ([db4be0e](https://github.com/nextcloud/mail/commit/db4be0e1f7e9797a750b3438ac6011009c761bb7))
* **deps:** bump @nextcloud/dialogs from 5.3.3 to ^5.3.4 (main) ([#9774](https://github.com/nextcloud/mail/issues/9774)) ([073aa64](https://github.com/nextcloud/mail/commit/073aa6432905fdfaec8657b8a390e3e05c2c4103))
* **deps:** bump @nextcloud/dialogs from 5.3.4 to ^5.3.5 (main) ([#9821](https://github.com/nextcloud/mail/issues/9821)) ([b4c5e64](https://github.com/nextcloud/mail/commit/b4c5e64cdcb56bc9069ae10e13c43770377bd196))
* **deps:** bump @nextcloud/dialogs from 5.3.5 to ^5.3.6 (main) ([#10027](https://github.com/nextcloud/mail/issues/10027)) ([b46c724](https://github.com/nextcloud/mail/commit/b46c724314a53da60c3454b3579c1fc7e7b89903))
* **deps:** bump @nextcloud/files from 3.1.0 to ^3.1.1 (main) ([#9499](https://github.com/nextcloud/mail/issues/9499)) ([97fbbe9](https://github.com/nextcloud/mail/commit/97fbbe9c40235e618eea9e9068fd9ec2df19c4d6))
* **deps:** bump @nextcloud/files from 3.2.1 to ^3.2.1 (main) ([#9597](https://github.com/nextcloud/mail/issues/9597)) ([44af721](https://github.com/nextcloud/mail/commit/44af72138b06c017d285e25e4781f5e7ed0d5286))
* **deps:** bump @nextcloud/files from 3.2.1 to ^3.4.0 (main) ([#9681](https://github.com/nextcloud/mail/issues/9681)) ([b7c8d7b](https://github.com/nextcloud/mail/commit/b7c8d7bb96491a243e36dcfd5ac2aef0e903acf0))
* **deps:** bump @nextcloud/files from 3.5.1 to ^3.5.1 (main) ([#9775](https://github.com/nextcloud/mail/issues/9775)) ([56d8a98](https://github.com/nextcloud/mail/commit/56d8a987e82632551408bae217622d7bcc8d2296))
* **deps:** bump @nextcloud/files from 3.5.1 to ^3.6.0 (main) ([#9907](https://github.com/nextcloud/mail/issues/9907)) ([7b20678](https://github.com/nextcloud/mail/commit/7b206782fe300d120922b131babf390035bacbbb))
* **deps:** bump @nextcloud/files from 3.6.0 to ^3.8.0 (main) ([#9948](https://github.com/nextcloud/mail/issues/9948)) ([6969d6e](https://github.com/nextcloud/mail/commit/6969d6eca3c3ed081ba1f8657a228f047faff789))
* **deps:** bump @nextcloud/initial-state from 2.1.0 to ^2.2.0 (main) ([#9643](https://github.com/nextcloud/mail/issues/9643)) ([aefeb7f](https://github.com/nextcloud/mail/commit/aefeb7ffb70d52f50d6a01e94ebd6fac19aaedb4))
* **deps:** bump @nextcloud/paths from 2.1.0 to ^2.2.0 (main) ([#9908](https://github.com/nextcloud/mail/issues/9908)) ([f296907](https://github.com/nextcloud/mail/commit/f2969072b4919a8cd37fabfa6e78d95741f97fa3))
* **deps:** bump @nextcloud/paths from 2.2.0 to ^2.2.1 (main) ([#9947](https://github.com/nextcloud/mail/issues/9947)) ([436e983](https://github.com/nextcloud/mail/commit/436e9837b503cb766d10b4d48a4fbefcdf6b459c))
* **deps:** bump @nextcloud/router from 3.0.0 to ^3.0.1 (main) ([#9596](https://github.com/nextcloud/mail/issues/9596)) ([cf9dd13](https://github.com/nextcloud/mail/commit/cf9dd13507befd1e45185e7aae1a0af5e3237655))
* **deps:** bump @nextcloud/vue from 8.11.0 to ^8.11.1 ([e91bea8](https://github.com/nextcloud/mail/commit/e91bea82bc4c77f9b2aa56286d8209c1a87c2ec6))
* **deps:** bump @nextcloud/vue from 8.11.1 to ^8.11.2 ([22a5ae4](https://github.com/nextcloud/mail/commit/22a5ae40b71057c1611a7773f34842d9ce38145c))
* **deps:** bump @nextcloud/vue from 8.11.2 to ^8.13.0 ([facc8ab](https://github.com/nextcloud/mail/commit/facc8ab626acdf7e910a8a287565d13340cfe702))
* **deps:** bump @nextcloud/vue from 8.13.0 to ^8.14.0 ([4ef4e64](https://github.com/nextcloud/mail/commit/4ef4e647d588d3514720fce7ebf946c82f63d6ad))
* **deps:** bump @nextcloud/vue from 8.14.0 to ^8.15.0 ([8e50f95](https://github.com/nextcloud/mail/commit/8e50f95e40a214a8e419ff8162cbb57686021741))
* **deps:** bump @nextcloud/vue from 8.15.0 to ^8.15.1 ([473069e](https://github.com/nextcloud/mail/commit/473069e4f2abcea0fbda41090719aef590a88419))
* **deps:** bump @nextcloud/vue from 8.15.1 to ^8.16.0 ([73b56f1](https://github.com/nextcloud/mail/commit/73b56f12c2f3da8c3000287cc38436b77bb88de5))
* **deps:** bump address-rfc2822 from 2.2.0 to ^2.2.1 (main) ([#9545](https://github.com/nextcloud/mail/issues/9545)) ([80e82ff](https://github.com/nextcloud/mail/commit/80e82ff3c3a6d920244e4d639e164da703d7e178))
* **deps:** bump address-rfc2822 from 2.2.1 to ^2.2.2 (main) ([#9618](https://github.com/nextcloud/mail/issues/9618)) ([5be2d9f](https://github.com/nextcloud/mail/commit/5be2d9fd204280cdd77a56dd471ea633c7539029))
* **deps:** bump bytestream/horde-mime from 2.13.0 to ^2.13.1 (main) ([#9704](https://github.com/nextcloud/mail/issues/9704)) ([4da6baa](https://github.com/nextcloud/mail/commit/4da6baaa2c5fa4acc6c0d55b905cab73b26c6a54))
* **deps:** bump bytestream/horde-mime from 2.13.1 to ^2.13.2 (main) ([#9997](https://github.com/nextcloud/mail/issues/9997)) ([4c7f3f1](https://github.com/nextcloud/mail/commit/4c7f3f11ba672a500d394ead8e4942c20f3d3af8))
* **deps:** bump core-js from 3.36.1 to ^3.37.1 (main) ([#9644](https://github.com/nextcloud/mail/issues/9644)) ([509d675](https://github.com/nextcloud/mail/commit/509d675bb71f7dc3d0cd6b887e1d74563e41301d))
* **deps:** bump dompurify from 3.0.10 to ^3.0.11 ([94b5093](https://github.com/nextcloud/mail/commit/94b50936147d27097154e872bb9debda5a965ac7))
* **deps:** bump dompurify from 3.0.11 to ^3.1.0 (main) ([#9546](https://github.com/nextcloud/mail/issues/9546)) ([1be5cce](https://github.com/nextcloud/mail/commit/1be5ccec727847914944ab1625b2f101c47cf046))
* **deps:** bump dompurify from 3.1.0 to ^3.1.3 (main) ([#9619](https://github.com/nextcloud/mail/issues/9619)) ([1451a01](https://github.com/nextcloud/mail/commit/1451a01cbe8c0e8566a0ebb6307cd36a76ed65e1))
* **deps:** bump dompurify from 3.1.3 to ^3.1.4 (main) ([#9680](https://github.com/nextcloud/mail/issues/9680)) ([981db11](https://github.com/nextcloud/mail/commit/981db11eb87ab737d08db59138405fb40859b4c3))
* **deps:** bump dompurify from 3.1.4 to ^3.1.5 (main) ([#9705](https://github.com/nextcloud/mail/issues/9705)) ([77ce832](https://github.com/nextcloud/mail/commit/77ce83240067203b6d0ab88bc0337c1d91792586))
* **deps:** bump dompurify from 3.1.5 to ^3.1.6 (main) ([#9822](https://github.com/nextcloud/mail/issues/9822)) ([f9a9a1c](https://github.com/nextcloud/mail/commit/f9a9a1c81df96fabd626b2c4a88da1b9fc611d2c))
* **deps:** bump iframe-resizer from 4.3.11 to ^4.4.4 (main) ([#9794](https://github.com/nextcloud/mail/issues/9794)) ([ae39845](https://github.com/nextcloud/mail/commit/ae3984539f28fa2d5ea574182e3c818f2ea49726))
* **deps:** bump iframe-resizer from 4.3.9 to ^4.3.11 (main) ([#9598](https://github.com/nextcloud/mail/issues/9598)) ([4b08392](https://github.com/nextcloud/mail/commit/4b0839274d0f604dd9a9e877bb15184697142862))
* **deps:** bump iframe-resizer from 4.4.4 to ^4.4.5 (main) ([#9872](https://github.com/nextcloud/mail/issues/9872)) ([a537c49](https://github.com/nextcloud/mail/commit/a537c491cbc391d8ae1f66376409f8fcbc6fb4d8))
* **deps:** bump nextcloud/kitinerary-bin from 1.0.2 to ^1.0.3 ([774ac9f](https://github.com/nextcloud/mail/commit/774ac9f5302a51d3526cc3811aa1fffc7448bcef))
* **deps:** bump nextcloud/kitinerary-sys from 1.0.1 to ^1.0.1 (main) ([#9974](https://github.com/nextcloud/mail/issues/9974)) ([c6cc2fa](https://github.com/nextcloud/mail/commit/c6cc2fa5f6ec7193c515181a80658f2e8c65b41a))
* **deps:** bump pinia from 2.1.7 to ^2.2.0 (main) ([#9949](https://github.com/nextcloud/mail/issues/9949)) ([9e67025](https://github.com/nextcloud/mail/commit/9e67025c1a7035ad052259feb6041e8dc4b9c8f1))
* **deps:** bump ramda from 0.29.1 to ^0.30.1 ([120def2](https://github.com/nextcloud/mail/commit/120def2ec0b1a81e169f5bc4e3a28fcfc358a8b7))
* **deps:** bump rubix/ml from 2.4.0 to v2.5.0 (main) ([#9874](https://github.com/nextcloud/mail/issues/9874)) ([2f36913](https://github.com/nextcloud/mail/commit/2f369131b5e1dbcd6856fc3fa6b270f7686fd03f))
* **deps:** bump sabberworm/php-css-parser from 8.5.1 to ^8.6.0 (main) ([#9909](https://github.com/nextcloud/mail/issues/9909)) ([08cc760](https://github.com/nextcloud/mail/commit/08cc760c7a5731704afb5ab422b0125a01bd9688))
* **deps:** bump stylelint from 16.2.1 to ^16.3.1 ([06c5976](https://github.com/nextcloud/mail/commit/06c59764a33c847a6a1d03ed0a203d18e24a4ffd))
* **deps:** bump webdav from 5.4.0 to ^5.5.0 ([8316cc7](https://github.com/nextcloud/mail/commit/8316cc729955e7c254a8ac703f36add04214e6e7))
* **deps:** Replace @nextcloud/vue-dashboard with @nextcloud/vue ([c1cc553](https://github.com/nextcloud/mail/commit/c1cc553ac9e8ce46d8ed643abfcea3e0430782f8))
* don't fail on missing mailbox stats ([1a613e8](https://github.com/nextcloud/mail/commit/1a613e80db91c58e814e212d897acf57dcf9575e))
* duplicate uid repair job failing on postgres ([48a149b](https://github.com/nextcloud/mail/commit/48a149bab4a86d7af86c5b082bec9da57b807c70))
* ellips the subject and position of the important icon ([7dcd2de](https://github.com/nextcloud/mail/commit/7dcd2de0453366462cd096acac33ca262d315146))
* **files:** add static icon for unknown user ([bdb5d30](https://github.com/nextcloud/mail/commit/bdb5d30bf8abf5ebef099459b21929e0da609d24))
* filter change icon to be shown only when the filter changes ([2e0ec87](https://github.com/nextcloud/mail/commit/2e0ec879b44e5be769ad7ba470b673d01fb47547))
* Fine-tune thread summary box design ([b508915](https://github.com/nextcloud/mail/commit/b50891524b9beafea33e199a6beb9b791af5b574))
* handle missing email in contact check ([eb11824](https://github.com/nextcloud/mail/commit/eb11824ea2091a930ef6aae95fc31834c53b62ba))
* honour MDN requests ([752b012](https://github.com/nextcloud/mail/commit/752b0127afecbd85f105761a8c22a909ea68e679))
* inconsistent encoding in saved sent messages ([a93b48e](https://github.com/nextcloud/mail/commit/a93b48eacf23490c9a4140a5028ebd81dfec9061))
* **integration:** Honor sharing to group members restriction ([fb4ae5e](https://github.com/nextcloud/mail/commit/fb4ae5e5d47b7ef5d3a4b2bef66d6b982009b6c9))
* **jobs:** Skip background jobs if no authentication is possible ([f1d3fda](https://github.com/nextcloud/mail/commit/f1d3fda3015c1ace393124a25cf588e37c879981))
* **l10n:** Update mailbox button text from "Edit name" to "Rename" ([1edb630](https://github.com/nextcloud/mail/commit/1edb63067701db0132e2479eb3e5ae1835c42415)), closes [#9108](https://github.com/nextcloud/mail/issues/9108)
* line ending for sieve scripts should be clrf ([1f54a38](https://github.com/nextcloud/mail/commit/1f54a387486d205703d9ec03692f200d3b7f021d)), closes [/www.rfc-editor.org/rfc/rfc5228#section-2](https://github.com//www.rfc-editor.org/rfc/rfc5228/issues/section-2)
* lost focus in reference picker ([13ceb7a](https://github.com/nextcloud/mail/commit/13ceb7a22897d19074f3cf5d8f74c6519c32be84))
* mailbox error empty content alignment ([4b73def](https://github.com/nextcloud/mail/commit/4b73defdc6a0b32812dfd1e52401072ad77975b0))
* make sure inbound_password uses null as default ([443749f](https://github.com/nextcloud/mail/commit/443749f9e6d9fd1122f42ffd5f67ae8e07af2b63))
* migrate advanced search to nc dialog ([558440f](https://github.com/nextcloud/mail/commit/558440f900a1591f637de0f34ba7df355b5a6d93))
* move delete duplicate uids repair step to a job ([c361202](https://github.com/nextcloud/mail/commit/c3612021789b2574feab452d0b64624c369ed1ec))
* **ocs-api:** fix attachment downloadUrl in ocs/v2.php/apps/mail/message/ID ([507ad47](https://github.com/nextcloud/mail/commit/507ad470145d961268e882c4c841e1f0d6889eec))
* **outbox:** add status for messages ([7c59040](https://github.com/nextcloud/mail/commit/7c590407858fa4076e76407eeb545234bd3782ce))
* **outbox:** handle indeterminate smtp errors ([a1daf35](https://github.com/nextcloud/mail/commit/a1daf351f83e27ea1fc4bac2d112966560f04501))
* **outbox:** handle missing raw message gracefully ([66d5e60](https://github.com/nextcloud/mail/commit/66d5e602ac207a908a0931cb66abdc5c64ed2b3d))
* **outbox:** Revive item subname ([25e6f60](https://github.com/nextcloud/mail/commit/25e6f6035b0909a1f267616194716d76dcaa8800))
* PHP deprecations ([d62ac04](https://github.com/nextcloud/mail/commit/d62ac040c4a64501425e068553f54b3331f5d412))
* php lint complaint ([6cda44b](https://github.com/nextcloud/mail/commit/6cda44bbd998b80b55f98210b41dfe4b1f469c02))
* **pi:** load more button needs to be clicked twice ([b57eec0](https://github.com/nextcloud/mail/commit/b57eec0b651a21665b2a399eae5d43599f7d2e91))
* **pi:** section title margins affected by global styles ([30abd01](https://github.com/nextcloud/mail/commit/30abd01dae8c2ab5bf53be814b4c1bde2a4a9303))
* polish list item ([b48317d](https://github.com/nextcloud/mail/commit/b48317d7305298805d6e440b0692bfc0e1c99428))
* **printing:** Fix long emails getting cut for print ([17e5e29](https://github.com/nextcloud/mail/commit/17e5e29f4a2f3336a420d03645fc7af9add2228c))
* **provisioning:** Set master password for passwordless sessions ([43ee642](https://github.com/nextcloud/mail/commit/43ee6428013c32e21d3cb86e7b68c85aa5f298df))
* remove deprecated prototype.substr() method ([2050173](https://github.com/nextcloud/mail/commit/20501734b0ba0e45dd095df61ea7a7a998d56317))
* remove duplicated license info covered by SPDX/reuse ([30b3dcc](https://github.com/nextcloud/mail/commit/30b3dccc1d8a4af84f31b8b0d04c00c4272c42a9))
* Remove instance name from share link add button ([e028f30](https://github.com/nextcloud/mail/commit/e028f30d32a922373bb9392b0fb6a6da2a567978))
* remove the app navigation spacer ([50ed62d](https://github.com/nextcloud/mail/commit/50ed62db6c88c82cbe9adf12d689efc5994cca28))
* remove the global styling from the composer list ([a45841d](https://github.com/nextcloud/mail/commit/a45841dcc046b827a3be7a1b93e19e73c6d7738d))
* remove ununsed package ([01c4faf](https://github.com/nextcloud/mail/commit/01c4fafb933f2538cfda8a688eebb8d57695359b))
* Sanitize forward slashes from name before generating url ([b29bfda](https://github.com/nextcloud/mail/commit/b29bfdadfac3be3bd5a9f8f2bfe2063c3a5f4719))
* save horde cache backend on imap client logout ([cda9d73](https://github.com/nextcloud/mail/commit/cda9d737c4c2754e88108e51057c53123fa51277))
* **search:** Improve quick search element alignment ([919c842](https://github.com/nextcloud/mail/commit/919c842ffa2056979e69e1a63b02f15980482a02))
* **search:** Show placeholder for the mailbox search input ([aa16c44](https://github.com/nextcloud/mail/commit/aa16c44c07c41e541fc5f20771de15dd793d8234))
* send imip when importing an event in mail ([880eca7](https://github.com/nextcloud/mail/commit/880eca7a8fe84706e57350d3f2ff01d804bdc117)), closes [/github.com/nextcloud/3rdparty/blob/ea2fabbd358c9e0f9dae43bcb242b0cf8ee0d178/sabre/vobject/lib/ITip/Broker.php#L245-L254](https://github.com//github.com/nextcloud/3rdparty/blob/ea2fabbd358c9e0f9dae43bcb242b0cf8ee0d178/sabre/vobject/lib/ITip/Broker.php/issues/L245-L254)
* Separate quick search and threads list with a line ([62c32c5](https://github.com/nextcloud/mail/commit/62c32c501508a38923880c83a9fc133c07f06f37))
* set link icon size explicitly ([4b7e433](https://github.com/nextcloud/mail/commit/4b7e4336c03ad5f04994311b02f527e76ad27c85))
* show image colour on dark theme ([3430895](https://github.com/nextcloud/mail/commit/343089506eed4090b936996d5d5effe89a6cbf02))
* Show message reply (all) action in toolbar ([5cd54bc](https://github.com/nextcloud/mail/commit/5cd54bca3ce721117c8047a7c1a04738b06da79a))
* Show text in empty mailbox view ([bc04ae1](https://github.com/nextcloud/mail/commit/bc04ae15a7c3477d74081b4421edce8be34c2312))
* smart reply button jump ([c29f6fe](https://github.com/nextcloud/mail/commit/c29f6fe6a568432308c128b37d6206b0e92949eb))
* **smime:** use whole certificate chain ([c6e28fd](https://github.com/nextcloud/mail/commit/c6e28fd59188ded88c3688a2e3d4988fd09df508)), closes [#9190](https://github.com/nextcloud/mail/issues/9190)
* **tags:** Hide Notjunk tag ([f81843d](https://github.com/nextcloud/mail/commit/f81843d41c759012e0d818463bee596e8b28bb90))
* thread padding ([f019d55](https://github.com/nextcloud/mail/commit/f019d55ca3668e3bc0ce24d312160dc40372458b))
* **threading:** Run manual garbage collection ([060923f](https://github.com/nextcloud/mail/commit/060923f2cb9634914f413e9f56c235b3715d9e83))
* use correct type for button, type for prop ([25b7595](https://github.com/nextcloud/mail/commit/25b759590ae9602392e7da702fa944957e5343c3))
* using shortcut to select drafts shouldnt open the composer ([6cd5628](https://github.com/nextcloud/mail/commit/6cd5628d39d0135ed7cf39df8b72d272e72e3885))


### Features

* add internal addresses ([aab2db9](https://github.com/nextcloud/mail/commit/aab2db92a618babc71fd285e56c11ddd7d2714d6))
* add JSON to occ mail export ([6b725ac](https://github.com/nextcloud/mail/commit/6b725ac3e1f5ecfc1ff05ddaa57d06fa3faa50d0))
* **composer:** Redesign inputs ([ff94dd9](https://github.com/nextcloud/mail/commit/ff94dd9c758f04d3fdb420dcdc6187e1e9977bec))
* **deps:** Drop Nextcloud 26, add 30 support ([7101ad2](https://github.com/nextcloud/mail/commit/7101ad2e4feafc1093d24fc2e3a7f2c16091a361))
* follow up reminders ([221ff11](https://github.com/nextcloud/mail/commit/221ff11205e703eb4757d3a7037959b80432d8b2))
* implement admin setting to disable classification by default ([96c1259](https://github.com/nextcloud/mail/commit/96c12592e888327f939308266db2a7bf6ba7e74a))
* improve the search bar icons ([9eb787c](https://github.com/nextcloud/mail/commit/9eb787c234e716f7bad9c216c1e9362374b99b0a))
* increaze the min and max width for horizontal view ([cef3c44](https://github.com/nextcloud/mail/commit/cef3c44c69f5644ba8cb66bf25a0c75fd1a91fde))
* Log AttachmentMissingError as a warning ([092c924](https://github.com/nextcloud/mail/commit/092c924fb70d0ac79cf823e660f09fdbe56d2388))
* make sieve filter form only resizable vertically ([951774e](https://github.com/nextcloud/mail/commit/951774e20d6f6eef5e106c565fe4fb2c4187a621))
* mark junk mail automatically as read and unimportant ([d9c20c5](https://github.com/nextcloud/mail/commit/d9c20c531b7bf3d13f03710867f3c31fe49450ce))
* **ocs:** add OCS extractor and workflow ([e854dc7](https://github.com/nextcloud/mail/commit/e854dc7efc7f97b98c9e1e12be949de0acdc7b04))
* **ocs:** document get api and amend return types ([929873d](https://github.com/nextcloud/mail/commit/929873db6bf6edd711030002606c63828c3ec7c6))
* **ocs:** notify of new messages and provide API endpoint to retrieve its contents ([71dbc51](https://github.com/nextcloud/mail/commit/71dbc51dca58d51f419e09cc4bff7bed49adbbd4))
* **ocs:** send a message via api ([253d0c8](https://github.com/nextcloud/mail/commit/253d0c81f4859b8e65d3940638d2e33ffed0ea27))
* search on subject, to, from by default ([f93ca3c](https://github.com/nextcloud/mail/commit/f93ca3c665c9dd486044141534dd538062b75b37))
* separate attachment from three-dot-menu ([3e6529c](https://github.com/nextcloud/mail/commit/3e6529c70b0ec7511ba6c6960266a36bffd9133a))
* **utility:** make json methods work the same for ([bfa0d7b](https://github.com/nextcloud/mail/commit/bfa0d7b3013212e21782e27518c8d7c79749a24c))


### Performance Improvements

* Use local caches for avatars ([bd75d0a](https://github.com/nextcloud/mail/commit/bd75d0abc4a397b3618ff74af0a49742079ed85b))


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




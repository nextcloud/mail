<template>
    <div id="account-form">
        <form method="post" v-on:submit.prevent.stop="onSubmit">
        <div class="hidden-visually">
            <!-- Hack for Safari and Chromium/Chrome which ignore autocomplete="off" -->
            <input type="text" id="fake_user" name="fake_user"
                autocomplete="off" tabindex="-1">
            <input type="password" id="fake_password" name="fake_password"
                autocomplete="off" tabindex="-1">
        </div>
        <fieldset>
            <div id="emptycontent" ref="emptyContent">
                <div class="icon-mail"></div>
                <h2>{{ t('mail', 'Connect your mail account') }}</h2>
            </div>
            <p class="grouptop">
                <input type="text"
                    ref="accountName"
                    name="account-name"
                    :placeholder="t('mail', 'Name')"
                    v-model="config.accountName"
                    autofocus />
            </p>
            <p class="groupmiddle">
                <input type="email"
                    ref="mailAddress"
                    name="mail-address"
                    :placeholder="t('mail', 'Mail Address')"
                    v-model="config.emailAddress"
                    required />
            </p>
            <p class="groupbottom">
                <input type="password"
                    name="mail-password"
                    ref="mailPassword"
                    :placeholder="t('mail', 'Password')"
                    v-model="config.password"
                    required />
            </p>

            <a class="toggle-manual-mode icon-caret-dark" v-on:click.stop="toggleManualMode">{{ t('mail', 'Manual configuration') }}</a>

            <div class="manual-inputs" ref="manualInputs">
                <p class="grouptop">
                    <input type="text"
                        name="imap-host"
                        ref="imapHost"
                        :placeholder="t('mail', 'IMAP Host')"
                        v-model="config.imapHost" />
                </p>
                <p class="groupmiddle" id="setup-imap-ssl">
                    <select id="setup-imap-ssl-mode"
                        v-model="config.imapSslMode"
                        ref="imapSslMode"
                        name="imap-sslmode"
                        :title="t('mail', 'IMAP security')"
                        v-on:change="onImapSslModeChange">
                        <option value="none">{{ t('mail', 'None') }}</option>
                        <option value="ssl">{{ t('mail', 'SSL/TLS') }}</option>
                        <option value="tls">{{ t('mail', 'STARTTLS') }}</option>
                    </select>
                </p>
                <p class="groupmiddle">
                    <input type="number"
                        ref="imapPort"
                        name="imap-port"
                        :placeholder="t('mail', 'IMAP Port')"
                        v-model="config.imapPort" />
                </p>
                <p class="groupmiddle">
                    <input type="text"
                        ref="imapUser"
                        name="imap-user"
                        :placeholder="t('mail', 'IMAP User')"
                        v-model="config.imapUser" />
                </p>
                <p class="groupbottom">
                    <input type="password"
                        ref="imapPassword"
                        name="imap-password"
                        :placeholder="t('mail', 'IMAP Password')"
                        v-model="config.imapPassword"
                        required />
                </p>
                <p class="grouptop">
                    <input type="text"
                        ref="smtpHost"
                        name="smtp-host"
                        :placeholder="t('mail', 'SMTP Host')"
                        v-model="config.smtpHost" />
                </p>
                <p class="groupmiddle" id="setup-smtp-ssl">
                    <select id="setup-smtp-ssl-mode"
                        v-model="config.smtpSslMode"
                        ref="smtpSslMode"
                        name="mail-smtp-sslmode"
                        :title="t('mail', 'SMTP security')"
                        v-on:change="onSmtpSslModeChange">
                        <option value="none">{{ t('mail', 'None') }}</option>
                        <option value="ssl">{{ t('mail', 'SSL/TLS') }}</option>
                        <option value="tls">{{ t('mail', 'STARTTLS') }}</option>
                    </select>
                </p>
                <p class="groupmiddle">
                    <input type="number"
                        ref="smtpPort"
                        name="smtp-port"
                        :placeholder="t('mail', 'SMTP Port')"
                        v-model="config.smtpPort" />
                </p>
                <p class="groupmiddle">
                    <input type="text"
                        ref="smtpUser"
                        name="smtp-user"
                        :placeholder="t('mail', 'SMTP User')"
                        v-model="config.smtpUser" />
                </p>
                <p class="groupbottom">
                    <input type="password"
                        ref="smtpPassword"
                        name="smtp-password"
                        :placeholder="t('mail', 'SMTP Password')"
                        v-model="config.smtpPassword"
                        required />
                </p>
            </div>

            <input type="submit"
                ref="submitButton"
                class="primary"
                :value="t('mail', 'Connect')"/>
            </fieldset>
        </form>
    </div>
</template>

<script>
export default {
  name: 'AccountForm',
  props: {
    settingsPage: Boolean
  },
  data() {
    return {
      firstToggle: true,
      config: {
        accountName: $('#user-displayname').text() || '',
        emailAddress: $('#user-email').text() || '',
        password: '',
        accountName: '',
        autoDetect: true,
        imapHost: '',
        imapPort: 993,
        imapSslMode: 'ssl',
        imapUser: '',
        imapPassword: '',
        smtpHost: '',
        smtpPort: 587,
        smtpSslMode: 'tls',
        smtpUser: '',
        smtpPassword: '',
      }
    };
  },
  mounted() {
    if (this.settingsPage) {
      $(this.$refs.emptyContent).hide();
      $(this.$refs.submitButton).val(t('mail', 'Save'));
    }

    if (this.config.autoDetect) {
      $(this.$refs.mailPassword).show();
      $(this.$refs.manualInputs).hide();
    } else {
      $(this.$refs.mailPassword).hide();
    }
  },
  methods: {
    toggleManualMode: function() {
      this.config.autoDetect = !this.config.autoDetect;

      $(this.$refs.manualInputs).slideToggle();
      this.$refs.imapHost.focus();

      if (!this.config.autoDetect) {
        if (this.firstToggle) {
          // Manual mode opened for the first time
          // -> copy email, password for imap&smtp
          const email = this.config.emailAddress;
          const password = this.config.password;

          this.config.imapUser = this.config.emailAddress;
          this.config.imapPassword = this.config.password;
          this.config.smtpUser = this.config.emailAddress;
          this.config.smtpPassword = this.config.password;
          this.firstToggle = false;
        }

        $(this.$refs.mailPassword).slideToggle(() => {
          $(this.$refs.mailAddress)
            .parent()
            .removeClass('groupmiddle')
            .addClass('groupbottom');
          // Focus imap host input
          this.$refs.imapHost.focus();
        });
      } else {
        $(this.$refs.mailPassword).slideToggle();
        $(this.$refs.mailAddress)
          .parent()
          .removeClass('groupbottom')
          .addClass('groupmiddle');
      }
    },
    onImapSslModeChange: function() {
      const imapDefaultPort = 143;
      const imapDefaultSecurePort = 993;

      switch (this.config.imapSslMode) {
        case 'none':
        case 'tls':
          this.config.imapPort = imapDefaultPort;
          break;
        case 'ssl':
          this.config.imapPort = imapDefaultSecurePort;
          break;
      }
    },
    onSmtpSslModeChange: function() {
      const smtpDefaultPort = 587;
      const smtpDefaultSecurePort = 465;

      switch (this.config.smtpSslMode) {
        case 'none':
        case 'tls':
          this.config.smtpPort = smtpDefaultPort;
          break;
        case 'ssl':
          this.config.smtpPort = smtpDefaultSecurePort;
          break;
      }
    },
    onSubmit: function() {
      const emailAddress = this.config.emailAddress
      const accountName = this.config.accountName;
      const password = this.config.password;

      let config = {
        accountName,
        emailAddress,
        password,
        autoDetect: true
      };

      // if manual setup is open, use manual values
      if (!this.config.autoDetect) {
        config = {
          accountName,
          emailAddress,
          password,
          imapHost: this.config.imapHost,
          imapPort: this.config.imapPort,
          imapSslMode: this.config.imapSslMode,
          imapUser: this.config.imapUser,
          imapPassword: this.config.imapPassword,
          smtpHost: this.config.smtpHost,
          smtpPort: this.config.smtpPort,
          smtpSslMode: this.config.smtpSslMode,
          smtpUser: this.config.smtpUser,
          smtpPassword: this.config.smtpPassword,
          autoDetect: false
        };
      }
      // TODO: Handle form submit
    }
  }
};
</script>

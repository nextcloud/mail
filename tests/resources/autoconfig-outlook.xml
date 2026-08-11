<clientConfig version="1.1">
  <emailProvider id="outlook.com">
    <!-- This is a temporary config until bug 1185366 is fixed.
         Without the fix, this config is fetched both for
         for all (!) Office365 users, due to the MX setup.
         So, this config here is mostly adjusted to Office365 users.
         It is unfortunately also used for foo@outlook.com email addresses
         (and only this exact domain, not outlook.de, not hotmail.com etc.),
         Bug 1185366 fixes this and means that Office365 will
         fetch the separate office365.com config.
         Once that fix is deployed to 95+% of end users,
         remove this config and add the outlook.com domain
         to the hotmail.com config. -->
    <domain>outlook.com</domain>
    <displayName>Microsoft</displayName>
    <displayShortName>Microsoft</displayShortName>
    <incomingServer type="imap">
      <hostname>outlook.office365.com</hostname>
      <port>993</port>
      <socketType>SSL</socketType>
      <authentication>password-cleartext</authentication>
      <username>%EMAILADDRESS%</username>
    </incomingServer>
    <incomingServer type="pop3">
      <hostname>outlook.office365.com</hostname>
      <port>995</port>
      <socketType>SSL</socketType>
      <authentication>password-cleartext</authentication>
      <username>%EMAILADDRESS%</username>
      <pop3>
        <leaveMessagesOnServer>true</leaveMessagesOnServer>
        <!-- Outlook.com docs specifically mention that POP3 deletes have effect on the main inbox on webmail and IMAP -->
      </pop3>
    </incomingServer>
    <incomingServer type="exchange">
      <hostname>outlook.office365.com</hostname>
      <port>443</port>
      <username>%EMAILADDRESS%</username>
      <socketType>SSL</socketType>
      <authentication>OAuth2</authentication>
      <owaURL>https://outlook.office365.com/owa/</owaURL>
      <ewsURL>https://outlook.office365.com/ews/exchange.asmx</ewsURL>
      <useGlobalPreferredServer>true</useGlobalPreferredServer>
    </incomingServer>
    <outgoingServer type="smtp">
      <hostname>smtp.office365.com</hostname>
      <port>587</port>
      <socketType>STARTTLS</socketType>
      <authentication>password-cleartext</authentication>
      <username>%EMAILADDRESS%</username>
    </outgoingServer>
  </emailProvider>
</clientConfig>

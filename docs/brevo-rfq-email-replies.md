# RFQ email replies with Brevo

External supplier RFQs are sent with Laravel's configured SMTP mailer. Replies can be captured in the RFQ detail page through Brevo Inbound Parsing.

## Configuration

1. Use a receiving subdomain that differs from the sender domain, such as `reply.example.com`, and verify it in Brevo.
2. Add Brevo's inbound MX records to that subdomain:
   - Priority 10: `inbound1.sendinblue.com.`
   - Priority 20: `inbound2.sendinblue.com.`
3. Set these backend environment values, then clear/rebuild Laravel's config cache:
   - `BREVO_RFQ_REPLY_DOMAIN=reply.example.com`
   - `BREVO_INBOUND_WEBHOOK_USERNAME=<random username>`
   - `BREVO_INBOUND_WEBHOOK_PASSWORD=<long random password>`
4. Create a Brevo webhook with type `inbound`, event `inboundEmailProcessed`, and domain `reply.example.com`. Set its URL to `https://<username>:<password>@<public-app-host>/api/webhooks/brevo/rfq-replies`.

The app adds a unique reply address to each external supplier RFQ email. Brevo sends replies to the webhook; the app matches the opaque reply token and verified supplier email, then displays the received message on the RFQ detail page. Quote values and attachments still need to be entered/reviewed through **Record Supplier Quote**.

Until the receiving domain, MX records, and webhook are configured, outgoing email still uses the current SMTP settings, but replies are not automatically collected.

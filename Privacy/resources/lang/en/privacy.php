<?php

return [
    'title'       => 'Consent to personal data processing',
    'admin_title' => 'Personal data',
    'version'     => 'Version :id of :date',

    // Template of the first text version: after installation the owner edits it in the admin panel
    'intro'                => 'By registering on the site ":site" (:url), I freely, of my own will and in my own interest give consent to the personal data controller — :operator — to process my personal data on the following terms.',
    'operator_placeholder' => '[full name or company name]',
    'data_title'           => 'What data is processed',
    'data'                 => 'Login, email address, IP address, browser and device information, as well as data I provide in my profile or publish on the site.',
    'purposes_title'       => 'Purposes of processing',
    'purposes'             => 'Registration and account maintenance, operation of site features, contacting me about the site, ensuring security and preventing abuse.',
    'actions_title'        => 'Actions with data',
    'actions'              => 'Collection, recording, organisation, accumulation, storage, updating, retrieval, use, anonymisation, blocking, deletion and destruction — with or without automated means. Data is not transferred to third parties except as required by law.',
    'term_title'           => 'Validity',
    'term'                 => 'Consent remains valid until the account is deleted or consent is withdrawn.',
    'withdraw_title'       => 'Withdrawal of consent',
    'withdraw'             => 'Consent can be withdrawn by sending a request to the controller at :contact. After withdrawal the controller stops processing and destroys the data within 30 days, unless otherwise required by law.',
    'contact_placeholder'  => '[email or postal address]',

    'accept'           => 'I give :link',
    'accept_link'      => 'consent to personal data processing',
    'social_notice'    => 'By registering via a social network, you give :link',
    'consent_required' => 'You must consent to personal data processing',

    'editor_hint'       => 'Replace the values in square brackets with the controller (full name or company name) and a contact for requests. A changed text is saved as a new version: previous versions are kept, and each consent shows which version the user accepted.',
    'publish'           => 'Publish new version',
    'view_page'         => 'Open consent page',
    'versions'          => 'Text versions',
    'current'           => 'current',
    'consents_count'    => 'Consents',
    'text_unchanged'    => 'The text has not changed, no new version was created',
    'version_published' => 'Version :id published',
];

# Charity Lookup (uk.co.cloudtopconsulting.charitylookup)

Looks up organisations on the Charity Commission for England & Wales
[Register of Charities API](https://api-portal.charitycommission.gov.uk/) by
registered charity number and updates the CiviCRM organisation.

This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [AGPL-3.0](LICENSE.txt).

## Setup

1. Register on the API portal and copy the **Primary key** from your profile.
2. In CiviCRM go to **Administer > System Settings > Charity Commission Lookup**
   (`civicrm/admin/setting/charitylookup`) and paste the key.
3. Optionally choose:
   - whether to overwrite the organisation name (the legal name is always updated),
   - whether to update address/phone/email/website, and which location type to use.

## Use

- On an organisation's summary, open **Actions > Charity Commission lookup**,
  check/enter the number (e.g. `1234567`, or `1234567-1` for a linked charity)
  and click **Look up and update**.
- APIv4:
  - `CharityLookup.fetch` with `registeredNumber` returns the raw register entry
    (nothing saved).
  - `CharityLookup.update` with `contactId` (and optional `registeredNumber`)
    updates one organisation. Without `contactId` it refreshes every
    organisation that has a number, least recently checked first (`limit`
    caps how many).
- A scheduled job **charitylookup_refresh** (daily, 50 at a time) is installed
  disabled; enable it under Scheduled Jobs to keep records current.

## What is updated

Custom field set **Charity Commission** (on Organisations): registered number,
register status, charity type, date registered/removed, removal reason, company
number, reporting status, latest financial year end, latest income and
expenditure, activities, trustees, last checked. All but the number are
read-only on screen.

Core fields: legal name (and optionally organisation name); address, phone,
email (of the configured location type, created or overwritten) and website
(type "Main", added if not already present). Blank values on the register do
not clear existing contact details.

## Notes

- The register's address is five free-text lines. The first becomes the street
  address, the last the town/city, and anything between becomes supplemental
  lines. Country is set to United Kingdom.
- Data comes from `allcharitydetailsV2` and `charityoverview` (activities).

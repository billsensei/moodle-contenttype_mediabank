@core @local @local_mediabank
Feature: Report of media bank items that nothing links to
  In order to clear space
  As an administrator
  I need to see which media bank items are not used anywhere

  Background:
    Given the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
    And the following "contentbank content" exist:
      | contextlevel | reference | contenttype           | user  | contentname | filepath                                                     |
      | Course       | C1        | contenttype_mediabank | admin | unusedpic   | /contentbank/contenttype/mediabank/tests/fixtures/sample.png |

  Scenario: An unlinked item is listed in the report
    Given I log in as "admin"
    When I navigate to "Reports > Media bank: items nobody links to" in site administration
    Then I should see "1 item(s) with no links"
    And I should see "unusedpic"

  Scenario: A minimum age hides recent uploads
    Given I log in as "admin"
    And I navigate to "Reports > Media bank: items nobody links to" in site administration
    When I set the field "Only items uploaded at least this many days ago" to "30"
    And I click on "//input[@type='submit' and @value='Show']" "xpath_element"
    Then I should not see "unusedpic"
    And I should see "Every item is linked from somewhere, or none is old enough."

@core @core_contentbank @contenttype @contenttype_mediabank
Feature: Converting a course's links does not reveal which items exist
  In order to keep media bank items private
  As a teacher
  I should get the same answer whether or not an item id exists

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "contentbank content" exist:
      | contextlevel | reference | contenttype           | user     | contentname | filepath                                                     |
      | Course       | C1        | contenttype_mediabank | admin    | sitepic     | /contentbank/contenttype/mediabank/tests/fixtures/sample.png |

  Scenario Outline: A course with no links gets the same page for any id and goes back to the course
    Given I log in as "admin"
    When <visit>
    Then I should see "This course has no links to the item."
    And I should not see "sitepic"
    And I click on "Continue" "button"
    And I should see "Course 1"
    And the url should match "/course/view\.php"

    Examples:
      | visit                                                                         |
      | I visit the media bank convert page for "sitepic" in course "C1"              |
      | I visit the media bank convert page for a missing item in course "C1"         |

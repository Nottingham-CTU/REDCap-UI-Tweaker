# Generated by Selenium IDE
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities

class TestT04SQLDESCRIPTIVEactiontag():
  def setup_method(self, method):
    self.driver = webdriver.Firefox()
    self.vars = {}
  
  def teardown_method(self, method):
    self.driver.quit()
  
  def test_t04SQLDESCRIPTIVEactiontag(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    elements = self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,\'REDCap UI Tweaker Test\')]")
    assert len(elements) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"sql-descriptive\"]")))
    self.driver.execute_script("sessionStorage.setItem(\'test-savedsetting\',$(\'[name=\"sql-descriptive\"]\').prop(\'checked\'))")
    element = self.driver.find_element(By.CSS_SELECTOR, "[name=\"sql-descriptive\"]")
    if element.is_selected() != True: element.click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]").click()
    self.driver.find_element(By.LINK_TEXT, "Basic Demography Form").click()
    self.driver.find_element(By.ID, "btn-last").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "field_type")))
    dropdown = self.driver.find_element(By.ID, "field_type")
    dropdown.find_element(By.CSS_SELECTOR, "*[value='sql']").click()
    self.driver.execute_script("$(\'#element_enum\').val(\"SELECT \'1\', \'b64:SGVyZSBpcyBzb21lIDxiPmV4YW1wbGU8L2I+IHRleHQu\'\");$(\'#field_annotation\').val(\"@SQLDESCRIPTIVE @DEFAULT=\'1\'\")")
    self.driver.find_element(By.ID, "field_name").send_keys("sql1")
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog-buttonset .ui-button[style*=\"bold\"]").click()
    time.sleep(2)
    self.driver.find_element(By.ID, "btn-last").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "field_type")))
    dropdown = self.driver.find_element(By.ID, "field_type")
    dropdown.find_element(By.CSS_SELECTOR, "*[value='sql']").click()
    self.driver.execute_script("$(\'#element_enum\').val(\"SELECT \'1\', \'url:Here%20is%20some%20%3Ci%3Eexample%3C%2Fi%3E%20text.\'\");$(\'#field_annotation\').val(\"@SQLDESCRIPTIVE @DEFAULT=\'1\'\")")
    self.driver.find_element(By.ID, "field_name").send_keys("sql2")
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog-buttonset .ui-button[style*=\"bold\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"record_status_dashboard.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "button.btn-rcgreen[onclick*=\"DataEntry/record_home.php\"]").click()
    self.driver.execute_script("if ($(\'#sql1-tr .labelrc\').html() == \'Here is some <b>example</b> text.\' && $(\'#sql2-tr .labelrc\').html() == \'Here is some <i>example</i> text.\' && $(\'#sql1-tr .data\').css(\'display\') == \'none\' && $(\'#sql2-tr .data\').css(\'display\') == \'none\') document.body.setAttribute(\'data-hassqldesc\',1)")
    elements = self.driver.find_elements(By.CSS_SELECTOR, "body[data-hassqldesc]")
    assert len(elements) > 0
    self.driver.execute_script("window.dataEntryFormValuesChanged=false")
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]").click()
    self.driver.find_element(By.LINK_TEXT, "Basic Demography Form").click()
    self.driver.find_element(By.CSS_SELECTOR, "#design-sql1 a[onclick*=\"deleteField\"]").click()
    self.driver.find_element(By.XPATH, "//button[contains(.,\'Delete\')]").click()
    self.driver.find_element(By.CSS_SELECTOR, "#design-sql2 a[onclick*=\"deleteField\"]").click()
    self.driver.find_element(By.XPATH, "//button[contains(.,\'Delete\')]").click()
    time.sleep(0.5)
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"sql-descriptive\"]")))
    self.driver.execute_script("$(\'[name=\"sql-descriptive\"]\').prop(\'checked\',sessionStorage.getItem(\'test-savedsetting\')==\'true\')")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
  

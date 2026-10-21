# Generated from Selenium IDE
# Test name: t20 - Full annotations in designer
import pytest
import time
import json
from selenium import webdriver
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions
from selenium.webdriver.support.wait import WebDriverWait

class Test_20_Full_annotations_in_designer:
  def setup_method(self, method):
    self.driver = self.selectedBrowser
    self.vars = {}
  def teardown_method(self, method):
    self.driver.quit()

  def test_20_Full_annotations_in_designer(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    assert len(self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,'REDCap UI Tweaker Test')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"expanded-annotations\"]")))
    self.driver.execute_script("sessionStorage.setItem('test-savedsetting',$('[name=\"expanded-annotations\"]').prop('checked'))")
    None if (element := self.driver.find_element(By.CSS_SELECTOR, "[name=\"expanded-annotations\"]")).is_selected() else element.click()
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]").click()
    self.driver.find_element(By.LINK_TEXT, "Basic Demography Form").click()
    self.driver.find_element(By.ID, "btn-last").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "field_type")))
    self.driver.find_element(By.ID, "field_type").find_element(By.CSS_SELECTOR, "*[value='sql']").click()
    self.driver.execute_script("$('#element_enum').val(\"SELECT '1', 'Option1' UNION SELECT '2', 'Option2'\");$('#field_annotation').val('Example annotation text')")
    self.driver.find_element(By.ID, "field_name").click()
    self.driver.find_element(By.ID, "field_name").send_keys("sql2")
    self.driver.find_element(By.CSS_SELECTOR, ".ui-dialog-buttonset .ui-button[style*=\"bold\"]").click()
    time.sleep(2)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"online_designer.php\"]:not([href*=\"logout=1\"])").click()
    self.driver.find_element(By.LINK_TEXT, "Basic Demography Form").click()
    assert len(self.driver.find_elements(By.XPATH, "//table[@id=\"design-sql2\"][contains(.,'Example annotation text')]")) > 0
    self.driver.find_element(By.CSS_SELECTOR, "#design-sql2 a[onclick*=\"deleteField\"]").click()
    self.driver.find_element(By.XPATH, "//button[contains(.,'Delete')]").click()
    time.sleep(0.5)
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ControlCenter/index.php\"]").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href$=\"ExternalModules/manager/control_center.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "[name=\"expanded-annotations\"]")))
    self.driver.execute_script("$('[name=\"expanded-annotations\"]').prop('checked',sessionStorage.getItem('test-savedsetting')=='true')")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)

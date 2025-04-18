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

class TestT30Namespacedreports():
  def setup_method(self, method):
    self.driver = webdriver.Firefox()
    self.vars = {}
  
  def teardown_method(self, method):
    self.driver.quit()
  
  def test_t30Namespacedreports(self):
    self.driver.get("http://127.0.0.1/")
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    elements = self.driver.find_elements(By.XPATH, "//*[@id=\"table-proj_table\"][contains(.,\'REDCap UI Tweaker Test\')]")
    assert len(elements) > 0
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"ExternalModules/manager/project.php\"]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button")))
    self.driver.find_element(By.CSS_SELECTOR, "tr[data-module=\"redcap_ui_tweaker\"] button.external-modules-configure-button").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.NAME, "report-namespaces")))
    element = self.driver.find_element(By.NAME, "report-namespaces")
    if element.is_selected() != True: element.click()
    self.driver.find_element(By.NAME, "report-namespace-name____0").send_keys("ns")
    self.driver.find_element(By.NAME, "report-namespace-roles____0").send_keys("TestRole2")
    self.driver.find_element(By.CSS_SELECTOR, "#external-modules-configure-modal .modal-footer .save").click()
    time.sleep(2)
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataExport/index.php\"]:not([href*=\"&logout=1\"])").click()
    elements = self.driver.find_elements(By.CSS_SELECTOR, ".create-new-report-btn")
    assert len(elements) > 0
    self.driver.find_element(By.CSS_SELECTOR, ".create-new-report-btn").click()
    self.driver.find_element(By.NAME, "__TITLE__").send_keys("R1")
    self.driver.execute_script("$(\'#south\').remove()")
    self.driver.find_element(By.ID, "save-report-btn").click()
    self.driver.find_element(By.XPATH, "//div[@aria-describedby=\'report_saved_success_dialog\']//button[contains(.,\'Close\')]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "south")))
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "Log out").click()
    time.sleep(1)
    self.driver.get("http://127.0.0.1/")
    self.driver.execute_script("$(\'#username\').val(JSON.parse(sessionStorage.getItem(\'test-userdetails\')).user2);$(\'#password\').val(JSON.parse(sessionStorage.getItem(\'test-userdetails\')).pass2)")
    self.driver.find_element(By.ID, "login_btn").click()
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "REDCap UI Tweaker Test").click()
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataExport/index.php\"]:not([href*=\"&logout=1\"])").click()
    elements = self.driver.find_elements(By.CSS_SELECTOR, ".create-new-report-btn")
    assert len(elements) > 0
    self.driver.find_element(By.CSS_SELECTOR, ".create-new-report-btn").click()
    self.driver.find_element(By.NAME, "__TITLE__").send_keys("R2")
    self.driver.execute_script("$(\'#south\').remove()")
    self.driver.find_element(By.ID, "save-report-btn").click()
    self.driver.find_element(By.XPATH, "//div[@aria-describedby=\'report_saved_success_dialog\']//button[contains(.,\'Close\')]").click()
    WebDriverWait(self.driver, 30).until(expected_conditions.presence_of_element_located((By.ID, "south")))
    self.driver.find_element(By.CSS_SELECTOR, "a[href*=\"DataExport/index.php\"]:not([href*=\"&logout=1\"])").click()
    elements = self.driver.find_elements(By.XPATH, "//span[@class=\'reportnum\']/following-sibling::a[contains(.,\'R1\')]")
    assert len(elements) > 0
    elements = self.driver.find_elements(By.XPATH, "//div[@class=\'hangf\' and contains(text(),\'ns\')]/following-sibling::div[contains(.,//span[@class=\'reportnum\']/following-sibling::a[contains(.,\'R1\')])]")
    assert len(elements) == 0
    elements = self.driver.find_elements(By.XPATH, "//div[@class=\'hangf\' and contains(text(),\'ns\')]/following-sibling::div[contains(.,//span[@class=\'reportnum\']/following-sibling::a[contains(.,\'R2\')])]")
    assert len(elements) > 0
    self.driver.find_element(By.XPATH, "//table[@id=\'table-report_list\']//tr[contains(.,\'R1\')]//button[contains(.,\'Edit\')]").click()
    elements = self.driver.find_elements(By.NAME, "__TITLE__")
    assert len(elements) == 0
    self.driver.find_element(By.LINK_TEXT, "My Projects").click()
    self.driver.find_element(By.LINK_TEXT, "Log out").click()
    time.sleep(1)
    self.driver.get("http://127.0.0.1/")
    self.driver.execute_script("$(\'#username\').val(JSON.parse(sessionStorage.getItem(\'test-userdetails\')).user1);$(\'#password\').val(JSON.parse(sessionStorage.getItem(\'test-userdetails\')).pass1)")
    self.driver.find_element(By.ID, "login_btn").click()
    time.sleep(2)
  

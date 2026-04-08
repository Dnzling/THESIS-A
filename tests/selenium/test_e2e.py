"""
Basic Selenium end-to-end checks for core modules.

Reads credentials and base URL from env vars:
  APP_URL   (default: http://127.0.0.1:8000)
  E2E_USER  (required)
  E2E_PASS  (required)
  HEADLESS  (optional, "1" to run headless)

Assumes a matching Chromedriver is on PATH.
Run with: python -m pytest tests/selenium/test_e2e.py
"""

import os
import time

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


BASE_URL = os.getenv("APP_URL", "http://127.0.0.1:8000")
USER = os.getenv("E2E_USER")
PASS = os.getenv("E2E_PASS")
HEADLESS = os.getenv("HEADLESS", "0") == "1"

if not USER or not PASS:
    pytest.skip("E2E_USER / E2E_PASS env vars are required for selenium tests", allow_module_level=True)


def make_driver():
    opts = Options()
    if HEADLESS:
        opts.add_argument("--headless=new")
    opts.add_argument("--start-maximized")
    # Speed up CI runs
    opts.add_argument("--disable-dev-shm-usage")
    opts.add_argument("--no-sandbox")
    return webdriver.Chrome(options=opts)


@pytest.fixture(scope="session")
def driver():
    drv = make_driver()
    yield drv
    drv.quit()


def wait_for_heading(driver, text, timeout=15):
    return WebDriverWait(driver, timeout).until(
        EC.visibility_of_element_located((By.XPATH, f"//*[self::h1 or self::h2][contains(., '{text}')]"))
    )


def login(driver):
    driver.get(f"{BASE_URL}/login")
    WebDriverWait(driver, 15).until(EC.visibility_of_element_located((By.NAME, "email"))).send_keys(USER)
    driver.find_element(By.NAME, "password").send_keys(PASS)
    driver.find_element(By.CSS_SELECTOR, "button[type=submit]").click()
    # Wait for any dashboard title to confirm login
    wait_for_heading(driver, "Dashboard", timeout=20)


@pytest.fixture(scope="session", autouse=True)
def authenticated(driver):
    login(driver)
    yield


def test_inventory_requisitions_list_loads(driver, authenticated):
    driver.get(f"{BASE_URL}/inventory/requisitions")
    wait_for_heading(driver, "Requisitions")
    # Table rows present or empty state visible
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located(
            (
                By.XPATH,
                "//table//tr | //*[contains(., 'No data') or contains(., 'No requisitions')]",
            )
        )
    )


def test_procurement_purchase_orders_list_loads(driver, authenticated):
    driver.get(f"{BASE_URL}/procurement/purchase-orders")
    wait_for_heading(driver, "Purchase Orders")
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located(
            (By.XPATH, "//table//tr | //*[contains(., 'No') and contains(., 'orders')]")
        )
    )


def test_finance_payables_list_loads(driver, authenticated):
    driver.get(f"{BASE_URL}/finance/payables")
    wait_for_heading(driver, "Payables")
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located(
            (By.XPATH, "//table//tr | //*[contains(., 'No') and contains(., 'payables')]")
        )
    )


def test_merchandising_dashboard_loads(driver, authenticated):
    driver.get(f"{BASE_URL}/merchandising/dashboard")
    wait_for_heading(driver, "Dashboard")
    # wait for any KPI cards / chart container
    WebDriverWait(driver, 15).until(
        EC.presence_of_element_located(
            (By.XPATH, "//*[contains(@class,'card') or contains(., 'KPI') or contains(., 'Summary')]")
        )
    )

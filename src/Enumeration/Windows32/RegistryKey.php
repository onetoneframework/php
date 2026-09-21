<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32;

enum RegistryKey: string
{
    case LOCAL_MACHINE = "HKEY_LOCAL_MACHINE";
    case CURRENT_USER = "HKEY_CURRENT_USER";
    case START_MENU_INTERNET = "HKEY_CURRENT_USER\\SOFTWARE\\Clients\\StartMenuInternet";
    case LOCAL_MACHINE_CUSTOM_EXECUTABLE_PATHS = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths";
    case CURRENT_USER_CUSTOM_EXECUTABLE_PATHS = "HKEY_CURRENT_USER\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths";
    case WOW6432NODE_GOOGLE_UPDATE = "HKEY_LOCAL_MACHINE\\SOFTWARE\\WOW6432Node\\Google\\Update";
    case UNINSTALL_PATHS = "SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Uninstall";
    case HKLM_SOFTWARE = "HKEY_LOCAL_MACHINE\\SOFTWARE";
    case HKCU_SOFTWARE = "HKEY_CURRENT_USER\\SOFTWARE";
    case HKCR = "HKEY_CLASSES_ROOT";
    case HKU = "HKEY_USERS";
    case HKCC = "HKEY_CURRENT_CONFIG";
    case RUN = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Run";
    case RUN_ONCE = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\RunOnce";
    case RUN_USER = "HKEY_CURRENT_USER\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Run";
    case RUN_ONCE_USER = "HKEY_CURRENT_USER\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\RunOnce";
    case SERVICES = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services";
    case CONTROL_SET = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet";
    case CURRENT_CONTROL_SET = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control";
    case WINDOWS_CURRENT_VERSION = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion";
    case WINDOWS_POLICIES = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows";
    case USER_POLICIES = "HKEY_CURRENT_USER\\SOFTWARE\\Policies\\Microsoft\\Windows";
    case EXPLORER = "HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Explorer";
    case SHELL_FOLDERS = "HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\Shell Folders";
    case USER_SHELL_FOLDERS = "HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Explorer\\User Shell Folders";
    case INTERNET_SETTINGS = "HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Internet Settings";
    case BROWSER_EMULATION = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Internet Explorer\\Main\\FeatureControl\\FEATURE_BROWSER_EMULATION";
    case EVENT_LOG = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\EventLog";
    case SECURITY = "HKEY_LOCAL_MACHINE\\SECURITY";
    case SAM = "HKEY_LOCAL_MACHINE\\SAM";
    case LSA = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Lsa";
    case NETWORK_PROVIDER = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\NetworkProvider";
    case NETWORK = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\Tcpip\\Parameters";
    case DNS = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\Dnscache\\Parameters";
    case DHCP = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\Dhcp";
    case USBSTOR = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\USBSTOR";
    case USB = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Enum\\USB";
    case MOUNTED_DEVICES = "HKEY_LOCAL_MACHINE\\SYSTEM\\MountedDevices";
    case PRINT_PROVIDERS = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Print\\Providers";
    case PRINTERS = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Print\\Printers";
    case FONTS = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\Fonts";
    case FILE_EXTS = "HKEY_CLASSES_ROOT\\SystemFileAssociations";
    case SHELL_ICONS = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Explorer\\Shell Icons";
    case POWER_SETTINGS = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Power";
    case TIME_ZONE = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\Time Zones";
    case WINLOGON = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\Winlogon";
    case PROFILE_LIST = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\ProfileList";
    case PROFILE_IMAGE_PATH = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\ProfileList\\%s\\ProfileImagePath";
    case WINDOWS_UPDATE = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows\\WindowsUpdate";
    case WINDOWS_DEFENDER = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows Defender";
    case WINDOWS_FIREWALL = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Services\\SharedAccess\\Parameters\\FirewallPolicy";
    case GROUP_POLICY = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies";
    case GROUP_POLICY_USER = "HKEY_CURRENT_USER\\SOFTWARE\\Policies";
    case INSTALLER = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows\\Installer";
    case SAFER = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft\\Windows\\Safer";
    case DCOM_CONFIG = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Classes\\AppID";
    case WMI = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\WBEM";
    case REMOTE_DESKTOP = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Terminal Server";
    case ENUM = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Enum";
    case DEVICE_CLASSES = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Class";
    case HARDWARE_PROFILES = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Hardware Profiles";
    case BOOT_EXECUTE = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Session Manager\\BootExecute";
    case BOOT_STATUS_POLICY = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\CrashControl";
    case ENVIRONMENT = "HKEY_LOCAL_MACHINE\\SYSTEM\\CurrentControlSet\\Control\\Session Manager\\Environment";
    case USER_ENVIRONMENT = "HKEY_CURRENT_USER\\Environment";
    case SOFTWARE_POLICIES_MICROSOFT = "HKEY_LOCAL_MACHINE\\SOFTWARE\\Policies\\Microsoft";
    case POLICIES_EXPLORER = "HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\Explorer";
    case POLICIES_SYSTEM = "HKEY_LOCAL_MACHINE\\Software\\Microsoft\\Windows\\CurrentVersion\\Policies\\System";
}

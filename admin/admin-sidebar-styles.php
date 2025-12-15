<style>
/* Admin Layout */
.admin-container {
    display: flex;
    min-height: 100vh;
    background-color: #f5f7fa;
}

/* Sidebar Styles */
.admin-sidebar {
    width: 250px;
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
    color: white;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    position: fixed;
    height: 100vh;
    z-index: 100;
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.admin-details h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.admin-role {
    font-size: 12px;
    opacity: 0.8;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 5px 0;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: #3498db;
}

.sidebar-nav a.active {
    background: rgba(52, 152, 219, 0.2);
    color: white;
    border-left-color: #3498db;
}

.sidebar-nav a i {
    width: 20px;
    text-align: center;
}

.nav-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 20px 0;
}

.sidebar-footer {
    padding: 15px 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 12px;
}

.system-status {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-indicator.online {
    background: #2ecc71;
    box-shadow: 0 0 5px #2ecc71;
}

.version {
    opacity: 0.6;
}

/* Main Content Area */
.admin-main {
    flex: 1;
    margin-left: 250px;
    padding: 20px;
    transition: margin-left 0.3s ease;
}

.admin-header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title h1 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-title p {
    margin: 0;
    color: #7f8c8d;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #2c3e50;
    font-size: 20px;
    cursor: pointer;
    padding: 5px;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.active {
        transform: translateX(0);
    }
    
    .admin-main {
        margin-left: 0;
    }
    
    .menu-toggle {
        display: block;
    }
}

/* Table Styles */
.table-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th,
.admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.admin-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #2c3e50;
}

.admin-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Status Badges */
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-under-investigation {
    background-color: #cce5ff;
    color: #004085;
}

.status-resolved {
    background-color: #d4edda;
    color: #155724;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 5px;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.btn-view {
    background-color: #e3f2fd;
    color: #1976d2;
}

.btn-edit {
    background-color: #e8f5e9;
    color: #388e3c;
}

.btn-delete {
    background-color: #ffebee;
    color: #d32f2f;
}

/* Form Controls */
.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
}

.btn:hover {
    opacity: 0.9;
}
</style>
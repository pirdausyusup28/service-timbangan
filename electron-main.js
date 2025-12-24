const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');

// Import service timbangan
const timbanganService = require('./timbangan-service-exe.js');

let mainWindow;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 800,
    height: 600,
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false
    },
    icon: path.join(__dirname, 'icon.ico')
  });

  // Load HTML interface
  mainWindow.loadFile('timbangan-ui.html');
  
  // Buka DevTools di development
  // mainWindow.webContents.openDevTools();
}

app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});
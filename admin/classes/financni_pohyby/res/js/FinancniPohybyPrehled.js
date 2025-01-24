function FinancniPohybyPrehled() {
    var frmPrehled;
    var btnZobrazitFPPdf;

    /**
     * Initialize components of UI and their events
     */
    this.init = function () {
        initComponents();
        initButtons();
    };

    /**
     * Search and instantiate UI elements
     */
    function initComponents() {
        frmPrehled = $('#form-prehled');
        btnZobrazitFPPdf = $('#btn-zoprazit-fp-pdf');
    }

    /**
     * Init button events
     */
    function initButtons() {
        btnZobrazitFPPdf.on('click', function () {
            frmPrehled.submit();
            return false;
        });
    }
}

FinancniPohybyPrehled._instance = null;

/**
 * Get single instance
 * @returns FinancniPohybyPrehled
 */
FinancniPohybyPrehled._getInstance = function () {
    if (FinancniPohybyPrehled._instance == null)
        FinancniPohybyPrehled._instance = new FinancniPohybyPrehled();
    return FinancniPohybyPrehled._instance;
};
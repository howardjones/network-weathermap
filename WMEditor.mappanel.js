Ext.namespace('WMEditor');

WMEditor.mappanel = Ext.extend(Ext.Panel, {
    initComponent: function()
    {
        var defConfig = {
            html: 'Map Interaction will be in here',
            bbar: {
                items: [
                    'Grid Snap: 5px',
                    '->',
                    '0, 0'
                ]
            }
        };

        Ext.applyIf(this, defConfig);

        WMEditor.mappanel.superclass.initComponent.call(this);
    }
});

Ext.reg('wmmappanel', WMEditor.mappanel);
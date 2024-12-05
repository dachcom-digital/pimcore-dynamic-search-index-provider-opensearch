class DsIndexProviderOpensearch {

    addLayoutToDsSettings(event) {

        this.dsSettings = event.detail.subject;
        this.dsContextFullConfig = pimcore.globalmanager.get('dynamic_search.context.full_configuration') || {};

        const dsActionsPanel = new Ext.panel.Panel({
            layout: 'hbox',
            title: 'DsIndexProviderOpenSearch',
            bodyPadding: 10,
            items: [

            ]
        });
        this.dsSettings.panel.add(dsActionsPanel);

        if (Object.keys(this.dsContextFullConfig).length === 0) {
            dsActionsPanel.add({
                xtype: 'displayfield',
                value: 'Error: no dynamic search context configured'
            });
            return;
        }

        dsActionsPanel.add({
            xtype: 'toolbar',
            docked: 'top',
            items: [
                {
                    scale: 'small',
                    margin: '0 10 0 0',
                    text: t('ds_index_provider_opensearch.actions.index.rebuild_mapping'),
                    icon: '/bundles/pimcoreadmin/img/flat-color-icons/data_configuration.svg',
                    menu: this.buildContextButtonMenu( 'rebuild_mapping'),
                }
            ]
        });

    }

    buildContextButtonMenu(action) {
        return Object.keys(this.dsContextFullConfig).map(function(context) {
            return {
                text: context,
                handler: function() {
                    Ext.Msg.confirm(
                        t(`ds_index_provider_opensearch.actions.index.${action}`) + ': ' + context,
                        t(`ds_index_provider_opensearch.actions.index.${action}.confirmation.message`),
                        function (confirmMsg) {

                            if (confirmMsg !== 'yes') {
                                return;
                            }

                            this.performContextIndexAction(context, action);

                        }.bind(this)
                    );
                }.bind(this)
            }
        }.bind(this))
    }

    performContextIndexAction(context, action) {
        let url = '';

        try {
            url = Routing.generate('ds_opensearch_controller_admin_index_' + action);
        } catch (e) {
            Ext.Msg.alert('Error', e.message);
            return;
        }

        Ext.Ajax.request({
            url: url,
            method: 'POST',
            params: {
                context: context
            },
            success: function(response) {
                if (response.status === 200) {
                    pimcore.helpers.showNotification(t('success'), t(`ds_index_provider_opensearch.actions.index.${action}.success`), 'success');
                } else {
                    pimcore.helpers.showNotification(t('error'), response.responseText, 'error');
                }
            }
        });
    }
}

const dsIndexProviderOpensearch = new DsIndexProviderOpensearch();

document.addEventListener('dynamic_search.event.settings.postBuildLayout', dsIndexProviderOpensearch.addLayoutToDsSettings.bind(dsIndexProviderOpensearch));

import BaseView = require('pimui/js/view/base');
import React from "react";
import ReactDOM from "react-dom";
import Index from 'akeneopimstructure/js/attribute-option/Index';

const __ = require('oro/translator');
const _ = require('underscore');

class Choices extends BaseView {
    private config: any;

    initialize(config: any): void {
        this.config = config.config;
        BaseView.prototype.initialize.apply(this, arguments);
    }

    configure(): JQueryPromise<any> {
        if (_.contains(this.config.activeForTypes, (this.getRoot() as any).getType())) {
            this.trigger('tab:register', {
                code: this.code,
                label: __(this.config.label)
            });
        }

        return BaseView.prototype.configure.apply(this, arguments);
    }

    render(): any {
        if (!_.contains(this.config.activeForTypes, (this.getRoot() as any).getType())) {
            return;
        }

        ReactDOM.render(
            <Index />,
            this.el
        );
        return this;
    }
}

export = Choices;

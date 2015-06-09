/*global _:false, _customizerCustomizerSettings:false, Backbone:false, wp:false */

(function( window, $, _, Backbone, wp, undefined ) {
	'use strict';

	var api = wp.customize,
		app = {},
		settings = _customizerCustomizerSettings;

	_.extend( app, { model: {}, view: {} } );

	app.model.Container = Backbone.Model.extend({
		defaults: {
			id: '',
			group: '',
			title: '',
			type: '',
			isVisible: true
		}
	});

	app.model.Containers = Backbone.Collection.extend({
		model: app.model.Container,

		initialize: function() {
			this.on( 'change:isVisible', this.hideContainer );
		},

		hideContainer: function( model ) {
			return wp.ajax.post( 'customizer_customizer_toggle_container', {
				container: model.toJSON(),
				nonce: settings.toggleNonce
			});
		}
	});

	app.view.Group = wp.Backbone.View.extend({
		className: 'group',
		tagName: 'div',
		template: wp.template( 'customizer-customizer-group' ),

		initialize: function( options ) {
			this.render();
		},

		render: function() {
			this.$el.html( this.template( this.options ) );

			this.views.add([
				new app.view.List({
					collection: this.collection,
					parent: this
				})
			]);
			return this;
		}
	});

	app.view.List = wp.Backbone.View.extend({
		className: 'list',
		tagName: 'ul',

		initialize: function( options ) {
			this.render();
		},

		render: function() {
			this.$el.empty();
			this.collection.each( this.addListItem, this );
			return this;
		},

		addListItem: function( listItem ) {
			var listItemView = new app.view.ListItem({ model: listItem });

			this.$el.append( listItemView.render().el );

			new app.view.Style({
				model: listItem
			});
		}
	});

	app.view.ListItem = wp.Backbone.View.extend({
		tagName: 'li',
		className: 'list-item',
		template: wp.template( 'customizer-customizer-list-item' ),

		events: {
			'change input': 'toggleVisibility'
		},

		render: function() {
			var isVisible = this.model.get( 'isVisible' );

			this.$el.html( this.template( this.model.toJSON() ) )
				.find( 'input[type="checkbox"]' ).prop( 'checked', ! isVisible );

			return this;
		},

		toggleVisibility: function() {
			var isVisible = this.model.get( 'isVisible' );
			this.model.set( 'isVisible', ! isVisible );
		}
	});

	app.view.Style = wp.Backbone.View.extend({
		tagName: 'style',

		initialize: function() {
			this.listenTo( this.model, 'change:isVisible', this.render );
			$( 'head' ).append( this.$el );
			this.render();
		},

		render: function() {
			var css = '',
				selector = this.model.get( 'selector' );

			if ( ! this.model.get( 'isVisible' ) ) {
				css  = selector + ',';
				css += selector + ' > * { display: none;}';
			}
			console.log( css );

			this.$el.html( css );
			return this;
		}
	});

	api.bind( 'ready', function() {
		var $container = $( '#customize-info' ).find( '.customize-panel-description, .accordion-section-content');

		_.each( settings.groups, function( group ) {
			var containers = new app.model.Containers();

			_.each( api.settings[ group.group ], function( container, id ) {
				var attributes = _.pick( container, 'id', 'title' );

				if ( undefined !== container.panel && '' !== container.panel ) {
					return;
				}

				_.extend( attributes, {
					id: id,
					group: group.group,
					isVisible: -1 === _.indexOf( settings.hidden[ group.group ], id ),
					selector: '#accordion-' + group.type + '-' + id,
					type: group.type
				});

				containers.push( attributes );
			});

			$container.append(
				new app.view.Group({
					collection: containers,
					title: group.title
				}).el
			);
		});
	});

})( window, jQuery, _, Backbone, wp );

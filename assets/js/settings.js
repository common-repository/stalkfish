/* global sf_settings_data, wp */
(function ($, wp) {
	$(
		function () {
			const {__} = wp.i18n;

			// Send Mock Event Data.
			function sendMockData(e) {
				if (e.preventDefault) {
					e.preventDefault();
				}

				const key = $('#sf_app_api_key').val();
				const elSubmitBtn = $( this );
				let status        = __( 'Sending', 'stalkfish' );
				elSubmitBtn.val( status );

				wp.ajax.post( 'mock_log_request', { key } ).done( function() {
					status = __( 'Sent', 'ang' );
					elSubmitBtn.val( status );
					elSubmitBtn.attr( 'disabled', 'disabled' );
					setTimeout(
						function () {
							elSubmitBtn.removeAttr( 'disabled' );
							elSubmitBtn.val( __( 'Send Sample Data', 'stalkfish' ) );
						},
						3000
					);
				}).fail( function() {
					status = __( 'Failed', 'stalkfish' );
					elSubmitBtn.val( status );
					setTimeout(
						function () {
							elSubmitBtn.val( __( 'Send Sample Data', 'stalkfish' ) );
						},
						2000
					);
				});

				return false;
			}

			$( '#sf-mock-data' ).on( 'click', sendMockData );

			/**
			 * Excludes
			 */
			var $excludeRows = $( '.sf-exclude-list tbody tr:not(.hidden)' );
			var $placeholderRow = $( '.sf-exclude-list tr.helper' );

			var initSettingsSelect2 = function( $rowsWithSelect2 ) {
				var $input_user;
				$( 'select.select2-select.pipe_or_context', $rowsWithSelect2 ).each(
					function( k, el ) {
						$( el ).select2(
							{
								allowClear: true,
								templateResult: function( item ) {
									if ( typeof item.id === 'undefined' ) {
										return item.text;
									}
									if ( item.id.indexOf( '-' ) === -1 ) {
										return $( '<span class="parent">' + item.text + '</span>' );
									}
									return $( '<span class="child">' + item.text + '</span>' );
								},
								matcher: function( params, data ) {
									var match = $.extend( true, {}, data );

									if ( null === params.term || $.trim( params.term ) === '' ) {
										return match;
									}

									var term = params.term.toLowerCase();

									if ( match.id.toLowerCase().indexOf( term ) >= 0 ) {
										return match;
									}

									if ( match.children ) {
										for ( var i = match.children.length - 1; i >= 0; i-- ) {
											var child = match.children[i];

											// Remove term from results if it doesn't match.
											if ( child.id.toLowerCase().indexOf( term ) === -1 ) {
												match.children.splice( i, 1 );
											}
										}

										if ( match.children.length > 0 ) {
											return match;
										}
									}

									return null;
								},
							}
						).on(
							'change', function() {
								var row = $( this ).closest( 'tr' ),
									pipe = $( this ).val();
								if ( pipe && 0 < pipe.indexOf( '-' ) ) {
									var pipe_split = pipe.split( '-' );
									pipe = pipe_split[0];
								}
								getActions( row, pipe );
							}
						);
					}
				);

				$( 'select.select2-select.action', $rowsWithSelect2 ).each(
					function( k, el ) {
						$( el ).select2(
							{
								allowClear: true,
							}
						);
					}
				);

				$( 'select.select2-select.author_or_role', $rowsWithSelect2 ).each(
					function( k, el ) {
						$input_user = $( el );
						$input_user.select2(
							{
								ajax: {
									type: 'POST',
									url: ajaxurl,
									dataType: 'json',
									quietMillis: 500,
									data: function( term, page ) {
										return {
											find: term,
											limit: 10,
											pager: page,
											action: 'sf_get_users',
											nonce: $input_user.data( 'nonce' ),
										};
									},
									processResults: function( response ) {
										var answer = {
											results: [
												{ text: '', id: '' },
												{ text: 'Roles', children: [] },
												{ text: 'Users', children: [] },
											],
										};

										if ( true !== response.success || undefined === response.data || true !== response.data.status ) {
											return answer;
										}

										if ( undefined === response.data.users || undefined === response.data.roles ) {
											return answer;
										}

										var roles = [];

										$.each(
											response.data.roles, function( id, text ) {
												roles.push(
													{
														id: id,
														text: text,
													}
												);
											}
										);

										answer.results[ 1 ].children = roles;
										answer.results[ 2 ].children = response.data.users;

										// Return the value of more so Select2 knows if more results can be loaded
										return answer;
									},
								},
								templateResult: function( object ) {
									var $result = $( '<div>' ).text( object.text );

									if ( 'undefined' !== typeof object.icon && object.icon ) {
										$result.prepend( $( '<img src="' + object.icon + '" class="sf-select2-icon">' ) );

										// Add more info to the container
										$result.attr( 'title', object.tooltip );
									}

									// Add more info to the container
									if ( 'undefined' !== typeof object.tooltip ) {
										$result.attr( 'title', object.tooltip );
									} else if ( 'undefined' !== typeof object.user_count ) {
										$result.attr( 'title', object.user_count );
									}

									return $result;
								},
								templateSelection: function( object ) {
									var $result = $( '<div>' ).text( object.text );

									if ( $.isNumeric( object.id ) && object.text.indexOf( 'icon-users' ) < 0 ) {
										$result.append( $( '<i class="icon16 icon-users"></i>' ) );
									}

									return $result;
								},
								allowClear: true,
								placeholder: $input_user.data( 'placeholder' ),
							}
						).on(
							'change', function() {
								var value = $( this ).select2( 'data' );

								$( this ).data( 'selected-id', value.id );
								$( this ).data( 'selected-text', value.text );
							}
						);
					}
				);

				$( '.exclude_rules_remove_rule_row', $rowsWithSelect2 ).on(
					'click', function( e ) {
						var $thisRow = $( this ).closest( 'tr' );

						$thisRow.remove();

						recalculate_rules_found();
						recalculate_rules_selected();

						e.preventDefault();
					}
				);
			};

			initSettingsSelect2( $excludeRows );

			$( 'select.select2-select.author_or_role', $excludeRows ).each(
				function() {
					var $option = $( '<option selected>' + $( this ).data( 'selected-text' ) + '</option>' ).val( $( this ).data( 'selected-id' ) );
					$( this ).append( $option ).trigger( 'change' );
				}
			);

			$( 'select.select2-select.pipe_or_context', $excludeRows ).each(
				function() {
					var parts = [
						$( this ).siblings( '.pipe' ).val(),
						$( this ).siblings( '.context' ).val(),
					];
					if ( parts[1] === '' ) {
						parts.splice( 1, 1 );
					}
					$( this ).val( parts.join( '-' ) ).trigger( 'change' );
				}
			);

			$( '#exclude_new_rule' ).on(
				'click', function() {
					var $newRow = $placeholderRow.clone();

					$newRow.removeAttr( 'class' );
					$newRow.insertBefore( $placeholderRow );

					initSettingsSelect2( $newRow );
					recalculate_rules_found();
					recalculate_rules_selected();
				}
			);

			$( '#exclude_remove_rules' ).on(
				'click', function() {
					var $excludeList = $( 'table.sf-exclude-list' ),
						selectedRows = $( 'tbody input.cb-select:checked', $excludeList ).closest( 'tr' );

					if ( ( $( 'tbody tr', $excludeList ).length - selectedRows.length ) >= 2 ) {
						selectedRows.remove();
					} else {
						$( ':input', selectedRows ).val( '' );
						$( selectedRows ).not( ':first' ).remove();
						$( '.select2-select', selectedRows ).select2( 'val', '' );
					}

					$excludeList.find( 'input.cb-select' ).prop( 'checked', false );

					recalculate_rules_found();
					recalculate_rules_selected();
				}
			);

			$( '.sf-exclude-list' ).closest( 'form' ).submit(
				function() {
					$( '.sf-exclude-list tbody tr.hidden', this ).each(
						function() {
							$( this ).find( ':input' ).removeAttr( 'name' );
						}
					);
					$( '.sf-exclude-list tbody tr:not(.hidden) select.select2-select.pipe_or_context', this ).each(
						function() {
							var parts = $( this ).val().split( '-' );
							$( this ).siblings( '.pipe' ).val( parts[0] );
							$( this ).siblings( '.context' ).val( parts.slice( 1 ).join( '-' ) );
							$( this ).removeAttr( 'name' );
						}
					);
					$( '.sf-exclude-list tbody tr:not(.hidden) select.select2-select.ip_address', this ).each(
						function() {
							var firstSelected = $( 'option:selected', this ).first();

							// Ugly hack to ensure we always pass an empty value or the order of rows gets messed up.
							if ( ! firstSelected.length ) {
								$( this ).append( '<option selected="selected"></option>' );
							}

							$( 'option:selected:not(:first)', this ).each(
								function() {
									firstSelected.attr( 'value', firstSelected.attr( 'value' ) + ',' + $( this ).attr( 'value' ) );
									$( this ).removeAttr( 'selected' );
								}
							);
						}
					);
				}
			);

			$( '.sf-exclude-list' ).closest( 'td' ).prev( 'th' ).hide();

			$( 'table.sf-exclude-list' ).on(
				'click', 'input.cb-select', function() {
					recalculate_rules_selected();
				}
			);

			function getActions( row, pipe ) {
				var trigger_action = $( '.select2-select.action', row ),
					action_value = trigger_action.val();

				trigger_action.empty();
				trigger_action.prop( 'disabled', true );

				var placeholder = $( '<option/>', { value: '', text: '' } );
				trigger_action.append( placeholder );

				var data = {
					action: 'sf_get_actions',
					pipe: pipe,
				};

				$.post(
					window.ajaxurl, data, function( response ) {
						var success = response.success,
							actions = response.data;
						if ( ! success ) {
							return;
						}
						for ( var key in actions ) {
							if ( actions.hasOwnProperty( key ) ) {
								var value = actions[key];
								var option = $( '<option/>', { value: key, text: value } );
								trigger_action.append( option );
							}
						}
						trigger_action.val( action_value );
						trigger_action.prop( 'disabled', false );
						$( document ).trigger( 'alert-actions-updated' );
					}
				);
			}

			function recalculate_rules_selected() {
				var $selectedRows = $( 'table.sf-exclude-list tbody tr:not( .hidden ) input.cb-select:checked' ),
					$deleteButton = $( '#exclude_remove_rules' );

				if ( 0 === $selectedRows.length ) {
					$deleteButton.prop( 'disabled', true );
				} else {
					$deleteButton.prop( 'disabled', false );
				}
			}

			function sf_regenerate_alt_rows( $rows ) {
				if ( ! $rows.length ) {
					return false;
				}

				$rows.removeClass( 'alternate' );

				$rows.each(
					function( index ) {
						$( this ).addClass( index % 2 ? '' : 'alternate' );
					}
				);
			};

			function recalculate_rules_found() {
				var $allRows = $( 'table.sf-exclude-list tbody tr:not( .hidden )' ),
					$noRulesFound = $( 'table.sf-exclude-list tbody tr.no-items' ),
					$selectAll = $( '.check-column.manage-column input.cb-select' ),
					$deleteButton = $( '#exclude_remove_rules' );

				if ( 0 === $allRows.length ) {
					$noRulesFound.show();
					$selectAll.prop( 'disabled', true );
					$deleteButton.prop( 'disabled', true );
				} else {
					$noRulesFound.hide();
					$selectAll.prop( 'disabled', false );
				}

				sf_regenerate_alt_rows( $allRows );
			}

			$( document ).ready(
				function() {
					recalculate_rules_found();
					recalculate_rules_selected();
				}
			);
		}
	);
}(jQuery, wp));

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export const variations = [
	{
		name: 'banner',
		title: __( 'Info Bar', 'spectra-blocks' ),
		icon: (
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path
					d="M2.68164 7.81525L20.7827 7.81525M4.80615 5.30768L8.21387 5.30768M17.083 5.30768L19.1719 5.30768M13.1616 5.30768L15.2505 5.30768M4.68164 2.93213H19.3184C20.4229 2.93213 21.3184 3.82756 21.3184 4.93213V19.0679C21.3184 20.1724 20.4229 21.0679 19.3184 21.0679H4.68164C3.57707 21.0679 2.68164 20.1724 2.68164 19.0679V4.93213C2.68164 3.82756 3.57707 2.93213 4.68164 2.93213Z"
					stroke="currentcolor"
					strokeWidth="0.8"
					strokeLinecap="round"
				/>
			</svg>
		),
		attributes: {
			variationSelected: true,
			variantType: 'banner',
			position: 'top-center',
			height: '50px',
			fixedHeight: false,
			hasOverlay: false,
			backgroundColor: '#f5f5f5',
			textColor: '#000000',
			closeIconSize: 20,
			closeIconColor: '#000000',
			style: {
				'spacing': {
					padding: {
						top: '8px',
						right: '8px',
						bottom: '8px',
						left: '8px'
					}
				},
				'height': '50px',
				'@tablet': {
					spacing: {
						padding: {
							top: '8px',
							right: '8px',
							bottom: '8px',
							left: '8px'
						}
					},
					height: '50px'
				},
				'@mobile': {
					spacing: {
						padding: {
							top: '16px',
							right: '16px',
							bottom: '16px',
							left: '16px'
						}
					},
					height: '50px'
				}
			},
			borderStyle: 'none',
		},

		scope: [ 'block' ],
		innerBlocks: [
			[
				'spectra/container',
				{
					align: 'none',
					variationSelected: true,
					width: '1100px',
					isBlockRootParent: true,
					style: {
						'spacing': {
							padding: {
								top: '0px',
								right: '0px',
								bottom: '0px',
								left: '0px'
							},
							margin: {
								top: '0',
								bottom: '0'
							},
							blockGap: '50px'
						},
						'width': '1100px',
						'@tablet': {
							spacing: {
								padding: {
									top: '0px',
									right: '0px',
									bottom: '0px',
									left: '0px'
								},
								blockGap: '50px'
							},
							layout: {
								type: 'grid',
								minimumColumnWidth: null,
								columnCount: 3
							},
							width: '700px'
						},
						'@mobile': {
							spacing: {
								padding: {
									top: '0px',
									right: '0px',
									bottom: '0px',
									left: '0px'
								},
								blockGap: '20px',
								margin: {
									top: '0',
									bottom: '0'
								}
							},
							layout: {
								type: 'grid',
								minimumColumnWidth: null,
								columnCount: 1
							},
							width: '300px'
						}
					},
					layout: {
						type: 'grid',
						minimumColumnWidth: null,
						columnCount: 3
					},
				},
				[
					[
						'spectra/container',
						{
							variationSelected: true,
							style: {
								'spacing': {
									padding: {
										top: '0',
										right: '0',
										bottom: '0',
										left: '0'
									}
								},
								'layout': {
									flexSize: null
								},
								'@tablet': {
									spacing: {
										padding: {
											top: '0px',
											right: '0px',
											bottom: '0px',
											left: '0px'
										}
									},
									layout: {
										type: 'grid',
										minimumColumnWidth: null,
										columnCount: 1
									}
								},
								'@mobile': {
									spacing: {
										padding: {
											top: '0px',
											right: '0px',
											bottom: '0px',
											left: '0px'
										}
									},
									layout: {
										type: 'flex',
										minimumColumnWidth: null,
										columnCount: 1,
										justifyContent: 'center'
									}
								}
							},
							layout: {
								type: 'grid',
								minimumColumnWidth: null
							},
						},
						[
							[
								'spectra/countdown',
								{
									endDateTime: '2025-09-30T07:21:21.674Z',
									displayEndDateTime: '2025-09-30 07:21:21',
									width: '',
									style: {
										'typography': {
											fontSize: '12px'
										},
										'layout': {
											selfStretch: 'fit',
											flexSize: null
										},
										'spacing': {
											blockGap: '0px',
											padding: {},
											margin: {}
										},
										'@tablet': {
											spacing: {
												blockGap: '0px'
											},
											typography: {
												fontSize: '12px'
											},
											layout: {
												type: 'flex',
												justifyContent: 'center',
												flexWrap: 'nowrap'
											}
										},
										'@mobile': {
											spacing: {
												blockGap: '2px',
												padding: {
													top: '0px',
													bottom: '0px',
													left: '0px',
													right: '0px'
												},
												margin: {
													top: '0px',
													bottom: '0px',
													left: '0px',
													right: '0px'
												}
											},
											typography: {
												fontSize: '12px'
											},
											layout: {
												type: 'grid',
												justifyContent: 'center',
												flexWrap: 'nowrap',
												columnCount: 4,
												minimumColumnWidth: null
											},
											width: ''
										},
										'color': {
											text: '#000000'
										}
									},
									layout: {
										type: 'grid',
										minimumColumnWidth: null,
										columnCount: 4
									},
								},
								[
									[
										'spectra/countdown-child-day',
										{
											lock: { move: true, remove: true },
											style: {
												'spacing': {
													blockGap: '0rem',
													padding: {
														top: '8px',
														right: '8px',
														bottom: '8px',
														left: '8px'
													}
												},
												'typography': {
													fontSize: 'medium'
												},
												'@tablet': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													},
													layout: {
														type: 'flex',
														flexWrap: 'nowrap',
														orientation: 'vertical',
														justifyContent: 'center'
													}
												},
												'@mobile': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													},
													layout: {
														type: 'flex',
														flexWrap: 'nowrap',
														orientation: 'vertical',
														justifyContent: 'center'
													}
												},
												'color': {
													text: '#000000'
												}
											},
											layout: {
												type: 'flex',
												flexWrap: 'nowrap',
												orientation: 'vertical',
												justifyContent: 'center'
											},
										},
										[
											[
												'spectra/countdown-child-number',
												{
													number: 5,
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
											[
												'spectra/countdown-child-label',
												{
													text: __(
														'Days',
														'spectra-blocks'
													),
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'typography': {
															fontSize: '12px'
														},
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
										],
									],
									[
										'spectra/countdown-child-hour',
										{
											lock: { move: true, remove: true },
											style: {
												'spacing': {
													blockGap: '0rem',
													padding: {
														top: '8px',
														right: '8px',
														bottom: '8px',
														left: '8px'
													}
												},
												'typography': {
													fontSize: 'medium'
												},
												'@tablet': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													}
												},
												'@mobile': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													}
												},
												'color': {
													text: '#000000'
												}
											},
										},
										[
											[
												'spectra/countdown-child-number',
												{
													number: 12,
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
											[
												'spectra/countdown-child-label',
												{
													text: __(
														'Hours',
														'spectra-blocks'
													),
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'typography': {
															fontSize: '12px'
														},
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
										],
									],
									[
										'spectra/countdown-child-minute',
										{
											lock: { move: true, remove: true },
											style: {
												'spacing': {
													blockGap: '0rem',
													padding: {
														top: '8px',
														right: '8px',
														bottom: '8px',
														left: '8px'
													}
												},
												'typography': {
													fontSize: 'medium'
												},
												'@tablet': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													}
												},
												'@mobile': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													}
												},
												'color': {
													text: '#000000'
												}
											},
										},
										[
											[
												'spectra/countdown-child-number',
												{
													number: 30,
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
											[
												'spectra/countdown-child-label',
												{
													text: __(
														'Minutes',
														'spectra-blocks'
													),
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'typography': {
															fontSize: '12px'
														},
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
										],
									],
									[
										'spectra/countdown-child-second',
										{
											lock: { move: true, remove: true },
											style: {
												'spacing': {
													blockGap: '0rem',
													padding: {
														top: '8px',
														right: '8px',
														bottom: '8px',
														left: '8px'
													}
												},
												'typography': {
													fontSize: 'medium'
												},
												'@tablet': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													}
												},
												'@mobile': {
													spacing: {
														blockGap: '0rem',
														padding: {
															top: '8px',
															right: '8px',
															bottom: '8px',
															left: '8px'
														}
													}
												},
												'color': {
													text: '#000000'
												}
											},
										},
										[
											[
												'spectra/countdown-child-number',
												{
													number: 45,
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'@tablet': {
															typography: {
																fontSize: '16px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '16px'
															}
														}
													},
												},
											],
											[
												'spectra/countdown-child-label',
												{
													text: __(
														'Seconds',
														'spectra-blocks'
													),
													lock: {
														move: true,
														remove: true,
													},
													style: {
														'typography': {
															fontSize: '12px'
														},
														'@tablet': {
															typography: {
																fontSize: '12px'
															}
														},
														'@mobile': {
															typography: {
																fontSize: '12px'
															}
														}
													},
												},
											],
										],
									],
								],
							],
						],
					],
					[
						'spectra/container',
						{
							variationSelected: true,
							style: {
								'spacing': {
									padding: {
										top: '0px',
										right: '0px',
										bottom: '0px',
										left: '0px'
									}
								},
								'typography': {
									textAlign: 'center'
								},
								'@tablet': {
									spacing: {
										padding: {
											top: '0px',
											right: '0px',
											bottom: '0px',
											left: '0px'
										}
									},
									typography: {
										textAlign: 'center'
									},
									layout: {
										type: 'flex'
									}
								},
								'@mobile': {
									spacing: {
										padding: {
											top: '0px',
											right: '0px',
											bottom: '0px',
											left: '0px'
										}
									},
									typography: {
										textAlign: 'center'
									},
									layout: {
										type: 'flex',
										justifyContent: 'center'
									}
								},
								'layout': {
									flexSize: null
								}
							},
							layout: {
								type: 'flex',
								justifyContent: 'center'
							},
						},
						[
							[
								'spectra/content',
								{
									tagName: 'h3',
									text: __(
										'Engage your visitors!',
										'spectra-blocks'
									),
									isRootBlock: false,
									style: {
										'@mobile': {
											typography: {}
										}
									},
								},
							],
						],
					],
					[
						'spectra/container',
						{
							variationSelected: true,
							style: {
								'spacing': {
									padding: {
										top: '0px',
										right: '0px',
										bottom: '0px',
										left: '0px'
									}
								},
								'@tablet': {
									spacing: {
										padding: {
											top: '0px',
											right: '0px',
											bottom: '0px',
											left: '0px'
										}
									},
									layout: {
										type: 'flex'
									}
								},
								'@mobile': {
									spacing: {
										padding: {
											top: '0px',
											right: '0px',
											bottom: '0px',
											left: '0px'
										}
									},
									layout: {
										type: 'flex',
										justifyContent: 'center'
									}
								},
								'layout': {
									flexSize: null
								}
							},
							layout: {
								type: 'flex',
								justifyContent: 'center'
							},
						},
						[
							[
								'spectra/buttons',
								{
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
										justifyContent: 'left',
										alignItems: 'center',
									},
									align: 'full',
									style: {
										typography: {
											fontSize: 'medium',
										},
										color: {
											text: '#000000',
										},
									},
								},
								[
									[
										'spectra/button',
										{
											text: __(
												'Call To Action',
												'spectra-blocks'
											),
											textColor: 'white',
											style: {
												'spacing': {
													padding: {
														top: '10px',
														right: '48px',
														bottom: '10px',
														left: '48px'
													}
												},
												'border': {
													radius: '8px'
												},
												'typography': {
													fontSize: '12px'
												},
												'@mobile': {
													spacing: {
														padding: {
															top: '10px',
															bottom: '10px',
															left: '32px',
															right: '32px'
														}
													},
													border: {
														radius: '8px'
													},
													typography: {
														fontSize: '12px'
													}
												},
												'color': {
													background: '#5733ff'
												}
											},
										},
									],
								],
							],
						],
					],
				],
			],
		],
	},
	{
		name: 'popup',
		title: __( 'Popup', 'spectra-blocks' ),
		icon: (
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M21 9.5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H15.25" stroke="currentcolor" strokeWidth="0.8" strokeLinecap="round" strokeLinejoin="round" />
				<path d="M18.375 2.375L21.875 5.875M18.375 5.875L21.875 2.375" stroke="currentcolor" strokeWidth="0.8" strokeLinecap="round" strokeLinejoin="round" />
				<path d="M17.8441 16.4643L17.8441 11.2857C17.8441 10.7137 17.5538 10.25 17.1958 10.25L13.9539 10.25C13.5959 10.25 13.3056 10.7137 13.3056 11.2857L13.3056 16.4643C13.3056 17.0363 13.5959 17.5 13.9539 17.5L17.1958 17.5C17.5538 17.5 17.8441 17.0363 17.8441 16.4643Z" stroke="currentcolor" strokeWidth="0.8" strokeLinecap="round" strokeLinejoin="round" />
				<path d="M10.7121 16.4643L10.7121 11.2857C10.7121 10.7137 10.4219 10.25 10.0638 10.25L6.82196 10.25C6.46388 10.25 6.1736 10.7137 6.1736 11.2857L6.1736 16.4643C6.1736 17.0363 6.46388 17.5 6.82196 17.5L10.0638 17.5C10.4219 17.5 10.7121 17.0363 10.7121 16.4643Z" stroke="currentcolor" strokeWidth="0.8" strokeLinecap="round" strokeLinejoin="round" />
				<path d="M8.2677 7.25H15.75" stroke="currentcolor" strokeWidth="0.8" strokeLinecap="round" strokeLinejoin="round" />
			</svg>
		),
		attributes: {
			variationSelected: true,
			variantType: 'popup',
			position: 'center-center',
			width: '500px',
			height: '320px',
			fixedHeight: false,
			hasOverlay: true,
			backgroundColor: '#f5f5f5',
			closeIconColor: '#000000',
			popupOverlayColor: '#000000bf',
			textColor: 'black',
			borderStyle: 'none',
			closeIconSize: 20,
			borderRadius: {
				top: 4,
				right: 4,
				bottom: 4,
				left: 4,
			},
			style: {
				'spacing': {
					padding: {
						top: '28px',
						right: '28px',
						bottom: '28px',
						left: '28px'
					}
				},
				'width': '500px',
				'height': '320px',
				'@tablet': {
					spacing: {
						padding: {
							top: '28px',
							right: '28px',
							bottom: '28px',
							left: '28px'
						}
					}
				},
				'@mobile': {
					spacing: {
						padding: {
							top: '14px',
							right: '14px',
							bottom: '14px',
							left: '14px'
						}
					}
				}
			},
		},
		isDefault: true,
		scope: [ 'block' ],
		innerBlocks: [
			[
				'spectra/container',
				{
					align: 'full',
					layout: {
						type: 'flex',
						orientation: 'vertical',
						justifyContent: 'center',
						alignItems: 'center',
					},
					variationSelected: true,
					isBlockRootParent: true,
					style: {
						spacing: {
							padding: {
								top: '0',
								right: '0',
								bottom: '0',
								left: '0',
							},
							blockGap: '20px',
						},
					},
				},
				[
					[
						'spectra/icon',
						{
							icon: 'circle-check',
						},
					],
					[
						'spectra/content',
						{
							tagName: 'h2',
							text: __(
								'Engage Your Visitors!',
								'spectra-blocks'
							),
							isRootBlock: false,
							style: {
								spacing: {
									margin: {
										top: '0',
										bottom: '0',
									},
								},
								typography: {
									textAlign: 'center',
								},
							},
						},
					],
					[
						'spectra/content',
						{
							tagName: 'p',
							text: __(
								'Create engaging popups and info bars to capture your visitors\' attention and drive conversions.',
								'spectra-blocks'
							),
							isRootBlock: false,
							style: {
								spacing: {
									margin: {
										top: '0',
										bottom: '0',
									},
								},
								typography: {
									textAlign: 'center',
								},
							},
						},
					],
					[
						'spectra/button',
						{
							text: __(
								'Call To Action',
								'spectra-blocks'
							),
							textColor: 'white',
							style: {
								spacing: {
									padding: {
										top: '8px',
										right: '24px',
										bottom: '8px',
										left: '24px'
									}
								},
								border: {
									radius: '8px'
								},
								color: {
									background: '#5733ff'
								}
							},
						},
					],
				],
			],
		],
	},
];

export default variations;

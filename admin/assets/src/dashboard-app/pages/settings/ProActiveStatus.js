import { __, sprintf } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import { CheckCircle2 } from 'lucide-react';

/**
 * Renders a "Pro Active" status for the My Account page.
 * Shown when spectra-blocks-pro is activated but has no licensing system.
 *
 * @since x.x.x
 * @return {Element} The rendered pro active status.
 */
const ProActiveStatus = () => {
	const translatedDesc = sprintf(
		/* translators: %s: plugin edition name */
		__( 'You are using %s version.', 'spectra-blocks' ),
		'<span class="text-text-primary font-medium">Spectra Blocks Pro</span>'
	);

	return (
		<Container
			align="stretch"
			className="bg-background-primary rounded-lg"
			containerType="flex"
			direction="column"
			gap="sm"
			justify="start"
		>
			<Container.Item className="flex flex-col space-y-2 shrink" style={ { flexShrink: '1' } }>
				<Label className="font-semibold mb-1" htmlFor="pro-active-status" size="md">
					{ __( 'License Key', 'spectra-blocks' ) }
				</Label>
				<div
					className="text-sm font-normal m-0 text-text-tertiary"
					dangerouslySetInnerHTML={ { __html: translatedDesc } }
				/>
			</Container.Item>

			<div
				className="flex flex-col sm:flex-row sm:items-center items-start justify-between gap-2 rounded-xl p-3"
				style={ { backgroundColor: '#F0FDF4', border: '1px solid #BBF7D0' } }
			>
				<span className="text-sm flex items-center gap-2">
					<CheckCircle2 size={ 16 } className="text-green-500 shrink-0" />
					<span>
						<strong className="font-semibold text-green-700">
							{ __( 'Pro Active', 'spectra-blocks' ) }
						</strong>{ ' ' }
						<span className="text-green-700">
							{ __( 'All premium features are unlocked.', 'spectra-blocks' ) }
						</span>
					</span>
				</span>
			</div>
		</Container>
	);
};

export default ProActiveStatus;

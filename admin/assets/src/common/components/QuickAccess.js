import { __ } from '@wordpress/i18n';
import { Badge } from '@bsf/force-ui';
import { Headset, BookOpen, CircleHelp, MessageSquare, Star, Rocket } from 'lucide-react';

const QUICK_ACCESS_ITEMS = [
	{
		icon: <Rocket size={ 20 } />,
		label: __( 'Setup Wizard', 'spectra-blocks' ),
		href: 'admin.php?page=spectra-blocks-onboarding',
		external: false,
		pro: false,
	},
	{
		icon: <Headset size={ 20 } />,
		label: __( 'VIP Priority Support', 'spectra-blocks' ),
		href: null,
		hrefKey: 'vipPrioritySupportUrl',
		external: true,
		pro: true,
	},
	{
		icon: <BookOpen size={ 20 } />,
		label: __( 'Knowledge Base', 'spectra-blocks' ),
		href: null,
		hrefKey: 'docsUrl',
		external: true,
		pro: false,
	},
	{
		icon: <CircleHelp size={ 20 } />,
		label: __( 'Help Center', 'spectra-blocks' ),
		href: null,
		hrefKey: 'docsUrl',
		external: true,
		pro: false,
	},
	{
		icon: <MessageSquare size={ 20 } />,
		label: __( 'Join the Community', 'spectra-blocks' ),
		href: 'https://www.facebook.com/groups/wpastra',
		external: true,
		pro: false,
	},
	{
		icon: <Star size={ 20 } />,
		label: __( 'Rate Us', 'spectra-blocks' ),
		href: 'https://wordpress.org/support/plugin/spectra-blocks/reviews/',
		external: true,
		pro: false,
	},
];

const QuickAccess = () => {
	return (
		<div
			className="bg-white border border-solid border-border-subtle"
			style={ { borderRadius: '16px', padding: '28px 32px' } }
		>
			<div className="font-semibold text-[18px] mb-[18px]" style={ { color: '#1C1E26' } }>
				{ __( 'Quick Access', 'spectra-blocks' ) }
			</div>
			<div className="grid grid-cols-2 gap-3">
				{ QUICK_ACCESS_ITEMS.map( ( item, index ) => {
					const href = item.href || spectra_blocks_admin_react?.spectra_website?.[ item.hrefKey ] || '#';
					const showPro = item.pro && spectra_blocks_react?.pro_plugin_status !== 'Activated';
					return (
						<a
							key={ index }
							href={ href }
							target={ item.external ? '_blank' : undefined }
							rel={ item.external ? 'noreferrer' : undefined }
							className="no-underline flex items-center gap-[10px] cursor-pointer hover:bg-[#f9f9f9]"
							style={ { border: '1px solid #EDEEF0', borderRadius: '10px', padding: '10px 14px' } }
						>
							<span style={ { color: '#4B5563', display: 'flex', flexShrink: 0 } }>{ item.icon }</span>
							<span className="text-[15px] font-semibold" style={ { color: '#1C1E26' } }>{ item.label }</span>
							{ showPro && (
								<Badge icon={ null } label="PRO" size="xxs" variant="inverse" />
							) }
						</a>
					);
				} ) }
			</div>
		</div>
	);
};

export default QuickAccess;

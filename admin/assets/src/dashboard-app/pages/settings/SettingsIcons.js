import { User, Settings, LayoutTemplate, PackageCheck, GitCompare, Zap, Eye, Blocks, Type, Bot } from 'lucide-react';

const SettingsIcons = {
    'asset-generation' : (
        <PackageCheck />
    ),
    'templates' : (
        <LayoutTemplate />
    ),
    'version-control' : (
        <GitCompare />
    ),
    'fonts-performance' : (
        <Zap />
    ),
    'global-settings' : (
        <Settings />
    ),
	'block-settings' : (
		<Blocks />
    ),
	'site-visibility' : (
		<Eye />
	),
    'account' : (
        <User />
    ),
    'font' : (
        <Type />
    ),
    'mcp' : (
        <Bot />
    )
};

export default SettingsIcons;

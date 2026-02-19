import { Skeleton } from '@bsf/force-ui';

/**
 * Page-level loading skeleton shown while a lazy route chunk is being fetched.
 *
 * @since x.x.x
 * @return {Element} Skeleton placeholder.
 */
const PageSkeleton = () => (
	<div className="md:p-8 sm:p-6 p-[0.7rem]">
		<Skeleton className="w-full h-8 rounded-md mb-4" />
		<Skeleton className="w-full h-48 rounded-md mb-4" />
		<Skeleton className="w-3/4 h-8 rounded-md" />
	</div>
);

export default PageSkeleton;

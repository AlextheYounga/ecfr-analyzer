<template>
	<ul class="space-y-2">
		<!-- Loop over items -->
		<li v-for="(item, index) in entities" :key="item.id ?? index">
			<!-- Clickable row -->
			<div class="flex items-center cursor-pointer select-none text-gray-700 hover:text-gray-900" @click="toggleExpand(item, index)">
				<!-- Arrow only shows if item has children -->
				<div v-if="item.type != 'section'" class="mr-1">
					<ChevronDownIcon v-if="expandedIndexes.includes(index)" class="w-5 h-5 transition-transform duration-200" />
					<ChevronRightIcon v-else class="w-5 h-5 transition-transform duration-200" />
				</div>

				<!-- Item text -->
				<div v-if="item.type == 'title'" class="font-bold py-1">
					{{ item.label }}
				</div>
				<a v-else-if="item.type == 'section'" :href="'/sections/' + item.id" class="text-gray-800 hover:text-blue-600 group flex gap-x-3 rounded-md py-1 text-sm/6 font-semibold">
					{{ item.label }}
				</a>
				<div v-else class="py-1">
					{{ item.label }}
				</div>
			</div>

			<!-- Children (recursive) -->
			<div v-if="expandedIndexes.includes(index) && item.children && item.children.length" class="pl-6 mt-1 border-l border-gray-300">
				<!-- Recursively render nested items -->
				<NestedDropdown :items="item.children" />
			</div>
		</li>
	</ul>
</template>

<script>
import axios from 'axios';
import { ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

export default {
	name: 'NestedDropdown',
	components: {
		ChevronRightIcon,
		ChevronDownIcon
	},
	props: {
		items: {
			type: Array,
			required: true
		}
	},
	data() {
		return {
			// We'll store which item indexes in this local component are expanded
			entities: [...this.items],
			expandedIndexes: []
		}
	},
	methods: {
		fetchChildren(item) {
			// Fetch children from API with axios
			axios.get(`/entities/${item.id}/children`)
				.then(response => {
					// Add children to item
					item.children = response.data
				})
				.catch(error => {
					console.error('Error fetching children:', error)
				})
		},
		toggleExpand(item, index) {
			if (!item.children) {
				item = this.fetchChildren(item)
			}
			if (this.expandedIndexes.includes(index)) {
				// Already expanded, so collapse it
				this.expandedIndexes = this.expandedIndexes.filter(i => i !== index)
			} else {
				// Not yet expanded, so expand it
				this.expandedIndexes.push(index)
			}
		}
	}
}
</script>
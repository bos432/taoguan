<template>
	<view class="xx-cascader-container">
		<view class="xx-cascader-show" :class="{'border' : border}">
			<view class="xx-cascader-view" @click="openDept">
				<text>{{modelVal}}</text>
			</view>
			<uni-icons class="clear-value" size="22" v-if="clearable && radioValue != ''" type="clear" @click="clear" />
			<uni-icons class="clear-value" size="16" v-if="!radioValue || radioValue == ''" type="right" />
		</view>
		<uni-popup ref="deptComm" type="bottom" :mask-click="false" border-radius="10px 10px 0 0">
			<view class="xx-cascader-popup-view">
				<view class="xx-cascader-popup-label">
					<text>{{placeholder}}</text>
					<uni-icons class="close" size="18" type="closeempty" @click="close"></uni-icons>
				</view>
				<view class="xx-cascader-popup-content">
					<scroll-view class="xx-cascader-area-x" :scroll-left="scrollLeft" scroll-x="true">
						<view class="xx-cascader-list">
							<view class="xx-cascader-item" v-for="(item,index) in selected" :key="index" :class="{
				          'xx-cascader-item-active':index == selectedIndex
				        }" @click="handleSelect($event,item,index)">
								<text>{{item.name || ''}}</text>
							</view>
						</view>
					</scroll-view>
					<scroll-view class="xx-cascader-area-y" :scroll-y="true">
						<view class="xx-cascader-y-item" :class="{'is-disabled': !!item.disable , selected: textSelected(item)}"
							v-for="(item, j) in list" :key="j" @click="handleNodeClick(item, selectedIndex, j)">
							<radio v-if="!!checkStrictly" @click.stop="radioClick(item)" style="transform:scale(0.7)"
								:value="item[props.value].toString()" :checked="item[props.value] == radioValue" />
							<text class="xx-cascader-y-text">{{item[props.label]}}</text>
							<uni-icons v-if="item[props.children]" class="xx-cascader-y-icon" type="right"></uni-icons>
						</view>
					</scroll-view>
				</view>
			</view>
		</uni-popup>

	</view>
</template>

<script>
	/**
	 * * @property {Array} options  数据源
	 * * @property {String} value or string  绑定的id
	 * * @property {String} placeholder  输入框占位文本
	 * * @property {Boolean} border = [true|false] 是否有边框
	 * * @property {Boolean} readonly = [true|false] 是否仅读
	 * * @property {Boolean} checkStrictly = [true|false] 可选择任意一级
	 * * @property {Boolean} clearable = [true|false] 是否显示清空按钮
	 * * @property {Boolean} showAllLevels = [true|false] 是否显示完整路径
	 * * @property {Object} props = {value: 'id',label: 'name',children: 'children'} 自定义字段规则
	 */

	export default {
		props: {
			options: {
				type: Array,
				default: () => {
					return []
				}
			},
			value: {
				type: [String, Number],
				default: null
			},
			placeholder: {
				type: String,
				default: '请选择'
			},
			checkStrictly: {
				type: Boolean,
				default: false,
			},
			border: {
				type: Boolean,
				default: true,
			},
			readonly: {
				type: Boolean,
				default: false,
			},
			clearable: {
				type: Boolean,
				default: false,
			},
			showAllLevels: {
				type: Boolean,
				default: false
			},
			props: {
				type: Object,
				default: () => {
					return {
						value: 'id',
						label: 'name',
						children: 'children'
					}
				}
			},
		},
		watch: {
			options: {
				handler(newValue, oldValue) {
					const list = this.assignLevelToTreeNodes(this.options)
					this.list = list
					this.allList = list
					this.$nextTick(() => {
						this.modelVal = this.filterLabel(true)
					})
				},
				immediate: true,
				deep: true
			},
			value: {
				handler(newValue, oldValue) {
					this.modelVal = this.filterLabel(this.isInit)
					this.isInit = false
				},
				immediate: true,
				deep: true
			}
		},
		data() {
			return {
				selected: [{
					id: null,
					name: '请选择',
				}],
				selectedIndex: 0,
				list: [],
				radioValue: this.value,
				allList: [],
				scrollLeft: 500,
				modelVal: '',
				isInit: true
			}
		},
		mounted() {

		},
		methods: {
			openDept() {
        if (!!this.readonly) return;
        this.$nextTick(() => {
          if (this.$refs.deptComm) {
            let res = this.$refs.deptComm.open("bottom");
          } else {
            console.error("deptComm 未定义");
          }
        });
			},
			close() {
				this.$refs.deptComm.close()
			},
			handleSelect(e, item, index) {
				if(!item.id) return
				this.selectedIndex = index;
				if (!item.children) {
					const data = this.findNodeById(this.allList, item.xx_parentId)
					const nodes = this.findNodeById(this.allList, item.id)
					const parentNode = this.findNodeById(this.allList, nodes.xx_parentId)
					if(data){
						this.list = data[this.props.children] || []		
					}else{
						this.list = parentNode[this.props.children]
					}
				} else {
					const list = this.allList
					const nodeIndex = this.selected.findIndex(v => v.id == item.id)
					const preNode = this.selected[nodeIndex == 0 ? 0 : nodeIndex - 1]
					const data = this.findNodeById(list, preNode.id)
					this.list = nodeIndex == 0 ? this.allList : data[this.props.children]
				}
			},
			handleNodeClick(item) {
				if (item[this.props.children] && item[this.props.children].length > 0) {
					this.list = item[this.props.children];
					this.selectedIndex = item.xx_level + 1;
					const data = {
						id: item[this.props.value],
						name: item[this.props.label],
						xx_level: item.xx_level,
						xx_parentId: item.xx_parentId,
						children: true
					};
					if (this.selected.some(v => v.xx_level === item.xx_level)) {
						this.$set(this.selected, item.xx_level, data);
						const list = JSON.parse(JSON.stringify(this.selected))
						const nodes = list.splice(0, item.xx_level + 1)
						nodes.push({
							id: null,
							name: '请选择',
							xx_level: item.xx_level + 1,
							children: true
						})
						this.selected = JSON.parse(JSON.stringify(nodes))
					} else {
						this.selected.splice(this.selected.length - 1, 0, data);
					}
				} else {
					if (!this.checkStrictly) {
						this.selectedIndex = item.xx_level;
						const datas = {
							id: item[this.props.value],
							name: item[this.props.label],
							xx_level: item.xx_level,
							xx_parentId: item.xx_parentId,
							children: false
						};
						const selected = JSON.parse(JSON.stringify(this.selected))
						selected[item.xx_level] = datas
						const list = JSON.parse(JSON.stringify(selected))
						const nodes = list.splice(0, item.xx_level + 1)
						this.selected = JSON.parse(JSON.stringify(nodes))
					}
				}
				if (!this.checkStrictly && !item[this.props.children]) {
					this.sendData(item);
				}
				this.$nextTick(() => {
					this.scrollLeft += 100
				})
			},
			sendData(item) {
				this.radioValue = item[this.props.value]
				this.$emit('input', item[this.props.value]);
        // this.$emit('input', item);
				this.close()
			},
			radioClick(item) {
				const data = {
					id: item[this.props.value],
					name: item[this.props.label],
					xx_level: item.xx_level,
					children: true
				};
				if (item[this.props.children] && item[this.props.children].length > 0) {
					if (this.selected.some(v => v.xx_level === item.xx_level)) {
						this.$set(this.selected, item.xx_level, data);
						const list = JSON.parse(JSON.stringify(this.selected))
						const nodes = list.splice(0, item.xx_level + 1)
						this.selected = JSON.parse(JSON.stringify(nodes))
					} else {
						data.children = false
						this.selected[this.selected.length - 1] = data
					}
				} else {
					const data = {
						id: item[this.props.value],
						name: item[this.props.label],
						xx_level: item.xx_level,
						children: false
					};
					this.selected[this.selected.length - 1] = data
				}
				this.sendData(item);
			},
			findNodeById(tree, id) {
				const list = []
				const defautData = {}
				const searchNode = (node) => {
					// 如果当前节点的 id 与目标 id 匹配，则返回当前节点的 name
					if (node[this.props.value] == id) {
						return node;
					}
					if (node[this.props.children]) {
						for (const childNode of node[this.props.children]) {
							const result = searchNode(childNode);
							// 如果找到匹配的节点，则返回结果
							if (result) {
								list.push(result)
								return result;
							}
						}
					}
					return null;
				}
				for (const node of tree) {
					const result = searchNode(node);
					if (result) {
						return result;
					}
				}
				return null;
			},
			assignLevelToTreeNodes(tree, xx_level = 0, xx_parentId = 0) {
				tree.forEach((node, index) => {
					node.xx_level = xx_level;
					node.xx_parentId = xx_parentId;
					if (node[this.props.children] && node[this.props.children].length > 0) {
						this.assignLevelToTreeNodes(node[this.props.children], xx_level + 1, node[this.props.value]);
					} else {
						delete node[this.props.children]
					}
				});
				return tree
			},
			filterLabel(isInit) {
				if (this.value && this.value != '') {
					const list = this.findNodeAndParents(this.allList, this.value)
					const names = list.map(v => v[this.props.label])
					if (this.showAllLevels) {
						if (isInit) {
							this.setSelectedData(list)
						}
						this.selectedIndex = this.selected.length - 1
						return names.toString().replace(/,/g, ' / ')
					} else {
						const node = this.findNodeById(this.allList, this.value)
						if (isInit) {
							this.setSelectedData(list)
						}
						return node[this.props.label] ?? this.placeholder
					}
				} else {
					this.clear()
					return this.placeholder
				}
			},
			setSelectedData(list) {
				if (list.length > 0) {
					const parantData = this.findNodeById(this.allList, list[list.length - 1].xx_parentId)
					this.list = parantData ? parantData[this.props.children] : this.allList
				}
				this.selected = list.map(v => {
					return {
						id: v[this.props.value],
						name: v[this.props.label],
						xx_level: v.xx_level,
						xx_parentId: v.xx_parentId,
						children: v[this.props.children]?.length ? true : false
					}
				})
				this.selectedIndex = this.selected.length - 1
			},
			clear() {
				this.radioValue = ''
				this.$emit('input', '');
				this.selected = [{
					id: null,
					name: '请选择',
				}]
				this.selectedIndex = 0
				this.list = this.allList
			},
			textSelected(item) {
				const list = this.selected.map(v => v.id)
				if (list.includes(item[this.props.value])) {
					return true
				} else {
					return false
				}
			},
			findNodeAndParents(data, id) {
				const result = [];
				const findNode = (node, parentId) => {
					if (node[this.props.value] == id) {
						result.push(node);
						return true;
					}
					if (node[this.props.children]) {
						for (const child of node[this.props.children]) {
							if (findNode(child, node[this.props.value])) {
								result.unshift(node);
								return true;
							}
						}
					}
					return false;
				}
				for (const node of data) {
					findNode(node);
				}
				return result;
			}
		}
	}
</script>

<style scoped lang="scss">
	$uni-primary: #e54d42 !default;

	.xx-cascader-container {
		width: 100%;
		height: 94rpx;
		position: relative;
		display: flex;
		align-items: center;

		.xx-cascader-show {
			height: 100%;
			line-height: 92rpx;
			padding-left: 10rpx;
			box-sizing: border-box;
			position: relative;
			display: flex;
			align-items: center;
			width: 100%;
			border: 1px solid transparent;
			height: 72rpx;
			border-radius: 10rpx;
			line-height: 72rpx;

			.xx-cascader-view {
				width: calc(100% - 40px);
				display: flex;

				text {
					white-space: nowrap;
					text-overflow: ellipsis;
					overflow: hidden;
				}
			}

			&.border {
				border: 1px solid #E5E6EB;
			}

			.clear-value {
				position: absolute;
				right: 10rpx;
				padding: 10rpx;
			}
		}

		.xx-cascader-popup-view {
			width: 100%;
			min-height: 600rpx;
			background-color: #ffffff;
			padding: 32rpx 10rpx;
			box-sizing: border-box;

			.xx-cascader-popup-label {
				width: 100%;
				position: relative;
				display: flex;
				align-items: center;
				justify-content: center;

				.close {
					position: absolute;
					right: 20rpx;
				}

				text {
					font-weight: bold;
					font-size: 32rpx;
					color: #1D2129;
					line-height: 44rpx;
				}
			}

			.xx-cascader-popup-content {
				.xx-cascader-area-x {
					::v-deep .uni-scroll-view {

						/* 隐藏垂直滚动条 */
						&::-webkit-scrollbar {
							width: 0;
						}

						/* 隐藏水平滚动条 */
						&::-webkit-scrollbar {
							height: 0;
						}
					}

					.xx-cascader-list {
						/* #ifndef APP-NVUE */
						display: flex;
						flex-wrap: nowrap;
						/* #endif */
						flex-direction: row;
						padding: 0 5px;
						border-bottom: 1px solid #f8f8f8;

						.xx-cascader-item {
							margin-left: 10px;
							margin-right: 10px;
							padding: 12px 0;
							text-align: center;
							/* #ifndef APP-NVUE */
							white-space: nowrap;
							/* #endif */
						}

						.xx-cascader-item-active {
							border-bottom: 2px solid $uni-primary;
							transition: display 0.5s all;
						}
					}
				}

				.xx-cascader-area-y {
					height: 500rpx;

					.xx-cascader-y-item {
						padding: 12px 15px;
						display: flex;
						align-items: center;
						position: relative;

						.xx-cascader-y-text {
							font-weight: normal;
							font-size: 28rpx;
							color: #4E5969;
							line-height: 40rpx;

						}

						.xx-cascader-y-icon {
							position: absolute;
							right: 0;
						}

						&.selected {

							.xx-cascader-y-text,
							.xx-cascader-y-icon {
								color: #e54d42 !important;
							}
						}

						&.is-disabled {
							pointer-events: none;
							color: #dcdfe6;
						}
					}
				}
			}
		}
	}
</style>
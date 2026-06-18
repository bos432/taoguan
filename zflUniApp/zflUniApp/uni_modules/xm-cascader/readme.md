# ## xm-cascader
> **组件名：xm-cascader**
> 代码块： `xmCascader`
---
xm-cascader 组件属性说明
---


# ## 重要提醒，该组件依赖uni-popup uni-icons

#### xm-cascader 组件属性说明

- **options:** Array，数据源
- **value or string:** String，绑定的id
- **placeholder:** String，输入框占位文本
- **border:** Boolean，是否有边框，默认为 false
- **readonly:** Boolean，是否仅读，默认为 false
- **checkStrictly:** Boolean，是否可选择任意一级，默认为 false
- **clearable:** Boolean，是否显示清空按钮，默认为 false
- **showAllLevels:** Boolean，是否显示完整路径，默认为 false
- **props:** Object，{value: 'id', label: 'name', children: 'children'}，自定义字段规则

#### xm-cascader 组件事件说明

- **@input:** function，选中事件


## 基本用法


```vue
<xm-cascader v-model="value" :options="list"></xm-cascader>
```

```javascript
[{
		"id": 1,
		"name": "Parent 1",
		"parentId": 0,
		"children": [{
				"id": 2,
				"name": "Child 1.1",
				"parentId": 1,
				"children": []
			},
			{
				"id": 3,
				"name": "Child 1.2",
				"parentId": 1,
				"children": []
			}
		]
	},
	{
		"id": 4,
		"name": "Parent 2",
		"parentId": 0,
		"children": [{
				"id": 5,
				"name": "Child 2.1",
				"parentId": 4,
				"children": []
			},
			{
				"id": 6,
				"name": "Child 2.2",
				"parentId": 4,
				"children": [{
					"id": 7,
					"name": "Grandchild 2.2.1",
					"parentId": 6,
					"children": []
				}]
			}
		]
	}]

```


## 无边框 -

```vue
<xm-cascader :border="false" v-model="value" :options="list"></xm-cascader>
```


## 可清空 -

```vue
<xm-cascader :clearable="false" v-model="value" :options="list"></xm-cascader>
```

## 可选择任意一级 -

```vue
<xm-cascader :clearable="true" v-model="value" :options="list"></xm-cascader>
```

## 显示完整路径 -

```vue
<xm-cascader :showAllLevels="true" v-model="value" :options="list"></xm-cascader>
```


## 绑定事件 -

```vue
<xm-cascader @input="change" v-model="value" :options="list"></xm-cascader>
```

```javascript
change(e){
  console.log('返回的ID:' + e)
}
```




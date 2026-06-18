// 组件名称命名为 getDate.ts
import dayjs from 'dayjs';
function getTimeRange(rangeType) {
    const start = new Date()
    const end = new Date()

    switch (rangeType) {
        // 今日
        case 'today':
            start.setHours(0, 0, 0, 0)
            end.setHours(23, 59, 59, 999)
            break
        // 昨日
        case 'yesterday':
            start.setDate(start.getDate() - 1)
            start.setHours(0, 0, 0, 0)
            end.setDate(end.getDate() - 1)
            end.setHours(23, 59, 59, 999)
            break
        // 最近七天
        case 'last7days':
            start.setDate(start.getDate() - 6)
            start.setHours(0, 0, 0, 0)
            end.setHours(23, 59, 59, 999)
            break
        // 这个月
        case 'currentMonth':
            start.setDate(1)
            start.setHours(0, 0, 0, 0)
            end.setMonth(end.getMonth() + 1)
            end.setDate(0)
            end.setHours(23, 59, 59, 999)
            break
        // 上个月
        case 'lastMonth':
            start.setMonth(start.getMonth() - 1)
            start.setDate(1)
            start.setHours(0, 0, 0, 0)
            end.setDate(0)
            end.setHours(23, 59, 59, 999)
            break
        // 最近3个月
        case 'before3Month':
            start.setMonth(start.getMonth() - 2)
            start.setDate(1)
            start.setHours(0, 0, 0, 0)
            end.setMonth(end.getMonth() + 1)
            end.setDate(0)
            end.setHours(23, 59, 59, 999)
            break
        default:
            console.error('Invalid range type')
            return
    }
    return [start, end]
}
function shortcuts(){
    return [{
        text: '今日',
        value: () => getTimeRange('today'),
    },
        // {
        //     text: '昨日',
        //     value: () => getTimeRange('yesterday'),
        // }, {
        //     text: '最近七日',
        //     value: () => getTimeRange('last7days'),
        // },
        {
            text: '近一月',
            value: () => getTimeRange('currentMonth'),
        },
        //     {
        //     text: '上个月',
        //     value: () => getTimeRange('lastMonth'),
        // },
        {
            text: '近三月',
            value: () => getTimeRange('before3Month'),
        }];
}
// 格式化日期时间为 YYYY-MM-DD HH:MM:SS
function formatDateTime(date) {
    if(!date){
        date = new Date();
    }
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}
//去掉秒
function formatTime(time) {
    if(!time){
        return "";
    }
    return dayjs(time).format('YYYY-MM-DD HH:mm');
}
//只显示日期
function formatDate(time) {
    if(!time){
        return "";
    }
    return dayjs(time).format('YYYY-MM-DD');
}
export {
    shortcuts,
    formatDateTime,
    formatTime,
    formatDate
}


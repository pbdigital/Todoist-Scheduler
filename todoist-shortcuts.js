function scheduleTasks() {
    //withUniqueClass(requireCursor(), TIMER_CLASSES, all, click);
    const selectedItems = document.querySelectorAll('li[aria-selected="true"]');
    console.log(selectedItems);

    const tasks = [];

    for (const item of selectedItems) {
      const durationElement = item.querySelector('li > div > div.task_list_item__content > div.task_list_item__info_tags > .task_list_item__info_tags__label > a > span.simple_content');
      const duration = durationElement ? parseInt(durationElement.textContent) : null;
      const taskNameElement = item.querySelector('.task_content');
      const taskName = taskNameElement ? taskNameElement.textContent : null;
      tasks.push({
        id: item.getAttribute('data-item-id'),
        name: taskName,
        duration: duration
      });
    }

    const options = {
      method: 'POST',
      body: JSON.stringify(tasks),
      headers: {
        'Content-Type': 'application/json'
      }
    };

    fetch('URL_OF_PHP_SCRIPT_GOES_HERE', options)
      .then(response => response.json())
      .then(data => {
        // handle the response data
      })
      .catch(error => {
        // handle the error
      });
    
  }
